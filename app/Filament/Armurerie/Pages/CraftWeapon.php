<?php

namespace App\Filament\Armurerie\Pages;

use App\Models\Weapon;
use App\Models\WeaponStock;
use App\Models\WeaponStockMovement;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

class CraftWeapon extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-fire';

    protected static string | \UnitEnum | null $navigationGroup = 'Armes';

    protected static ?string $navigationLabel = 'Crafter';

    protected static ?string $title = 'Crafter une arme';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.armurerie.pages.craft-weapon';

    public ?int $weapon_id = null;
    public int $quantity = 1;

    // Constants for raw material calculations
    private const POLYMERE_PETROLE_RATE = 5;
    private const POLYMERE_COST = 4500;
    private const METAL_MINERAI_RATE = 5;
    private const RESSORT_METAL_RATE = 1;
    private const RESSORT_MINERAI_RATE = 3;
    private const PLANS_PER_ITEM = 4;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('weapon_id')
                ->label('Arme à crafter')
                ->options(Weapon::active()->orderBy('sort_order')->pluck('name', 'id'))
                ->required()
                ->live()
                ->afterStateUpdated(fn () => $this->quantity = 1),
            TextInput::make('quantity')
                ->label('Quantité')
                ->numeric()
                ->minValue(1)
                ->maxValue(99)
                ->default(1)
                ->required()
                ->live(debounce: 300),
        ]);
    }

    public function getPreviewProperty(): ?array
    {
        if (! $this->weapon_id) {
            return null;
        }

        $weapon = Weapon::find($this->weapon_id);
        if (! $weapon) {
            return null;
        }

        $qty = max(1, (int) $this->quantity);
        $recipe = $weapon->recipe;
        $needs = [];
        $canCraft = true;

        // Plan uses
        $planStock = WeaponStock::where('slug', 'plan_' . $weapon->slug)->first();
        $planNeed = $recipe['plans'] * $qty;
        $planHave = $planStock ? $planStock->quantity : 0;
        $planPhysical = (int) floor($planHave / self::PLANS_PER_ITEM);
        $needs[] = [
            'name' => 'Plans ' . $weapon->name,
            'slug' => 'plan_' . $weapon->slug,
            'need' => $planNeed,
            'have' => $planHave,
            'ok' => $planHave >= $planNeed,
            'type' => 'plan',
            'physical' => $planPhysical,
        ];
        if ($planHave < $planNeed) $canCraft = false;

        // Material pieces
        $pieceLabels = [
            'ressort' => 'Ressort', 'canon' => 'Canon', 'poignee' => 'Poignée',
            'corp' => 'Corp de pistolet', 'metal' => 'Pièce de métal', 'polymere' => 'Polymère',
        ];

        foreach ($pieceLabels as $slug => $label) {
            $need = ($recipe[$slug] ?? 0) * $qty;
            if ($need <= 0) continue;
            $stock = WeaponStock::where('slug', $slug)->first();
            $have = $stock ? $stock->quantity : 0;
            $needs[] = [
                'name' => $label,
                'slug' => $slug,
                'need' => $need,
                'have' => $have,
                'ok' => $have >= $need,
                'type' => 'piece',
            ];
            if ($have < $need) $canCraft = false;
        }

        // Raw material breakdown
        $totalRessort = ($recipe['ressort'] ?? 0) * $qty;
        $totalMetal = ($recipe['metal'] ?? 0) * $qty;
        $totalPolymere = ($recipe['polymere'] ?? 0) * $qty;

        $metalForRessorts = $totalRessort * self::RESSORT_METAL_RATE;
        $mineraiForRessorts = $totalRessort * self::RESSORT_MINERAI_RATE;
        $totalMetalPieces = $totalMetal + $metalForRessorts;
        $totalMinerai = ($totalMetalPieces * self::METAL_MINERAI_RATE) + $mineraiForRessorts;
        $totalPetrole = $totalPolymere * self::POLYMERE_PETROLE_RATE;
        $polymereCost = $totalPolymere * self::POLYMERE_COST;

        $mineraiStock = WeaponStock::where('slug', 'minerai')->first();
        $petroleStock = WeaponStock::where('slug', 'petrole')->first();

        $rawMaterials = [
            ['name' => 'Minerais de fer', 'need' => $totalMinerai, 'have' => $mineraiStock?->quantity ?? 0],
            ['name' => 'Pétroles', 'need' => $totalPetrole, 'have' => $petroleStock?->quantity ?? 0],
        ];

        // Craft time
        $craftTime = $weapon->craft_time_seconds ? $weapon->craft_time_seconds * $qty : null;

        return [
            'weapon' => $weapon,
            'qty' => $qty,
            'recipe' => $recipe,
            'needs' => $needs,
            'canCraft' => $canCraft,
            'rawMaterials' => $rawMaterials,
            'craftBreakdown' => [
                'ressorts' => $totalRessort,
                'metal_for_ressorts' => $metalForRessorts,
                'minerai_for_ressorts' => $mineraiForRessorts,
                'total_metal_pieces' => $totalMetalPieces,
                'total_minerai' => $totalMinerai,
                'total_petrole' => $totalPetrole,
                'polymere_cost' => $polymereCost,
            ],
            'craftTime' => $craftTime,
            'planPhysical' => $planPhysical,
        ];
    }

    public function getWeaponsOverviewProperty(): array
    {
        $weapons = Weapon::active()->orderBy('sort_order')->get();
        $overview = [];

        foreach ($weapons as $w) {
            $finishedStock = WeaponStock::where('slug', 'weapon_' . $w->slug)->first();
            $planStock = WeaponStock::where('slug', 'plan_' . $w->slug)->first();
            $overview[] = [
                'id' => $w->id,
                'name' => $w->name,
                'finished' => $finishedStock?->quantity ?? 0,
                'plan_uses' => $planStock?->quantity ?? 0,
                'plan_physical' => $planStock ? (int) floor($planStock->quantity / self::PLANS_PER_ITEM) : 0,
                'craft_time' => $w->craft_time_seconds,
            ];
        }

        return $overview;
    }

    public function getPiecesStockProperty(): array
    {
        $pieces = WeaponStock::where('category', 'piece')->orderBy('sort_order')->get();
        $raw = WeaponStock::where('category', 'raw_material')->orderBy('sort_order')->get();

        return [
            'pieces' => $pieces->map(fn ($s) => ['name' => $s->name, 'qty' => $s->quantity, 'slug' => $s->slug])->toArray(),
            'raw' => $raw->map(fn ($s) => ['name' => $s->name, 'qty' => $s->quantity, 'slug' => $s->slug])->toArray(),
        ];
    }

    public function craft(): void
    {
        $this->validate();

        $weapon = Weapon::findOrFail($this->weapon_id);
        $qty = max(1, (int) $this->quantity);
        $userId = auth()->id();

        try {
            DB::transaction(function () use ($weapon, $qty, $userId) {
                $recipe = $weapon->recipe;

                // Consume plan uses
                $planStock = WeaponStock::where('slug', 'plan_' . $weapon->slug)->lockForUpdate()->firstOrFail();
                $planNeed = $recipe['plans'] * $qty;
                if ($planStock->quantity < $planNeed) {
                    throw new \RuntimeException("Pas assez de plans pour {$weapon->name}");
                }
                $planStock->removeQuantity($planNeed);
                WeaponStockMovement::create([
                    'weapon_stock_id' => $planStock->id,
                    'quantity_change' => -$planNeed,
                    'reason' => 'craft_consume',
                    'user_id' => $userId,
                    'notes' => "Craft {$qty}× {$weapon->name}",
                    'created_at' => now(),
                ]);

                // Consume materials
                $materialSlugs = ['ressort', 'canon', 'poignee', 'corp', 'metal', 'polymere'];
                foreach ($materialSlugs as $slug) {
                    $need = ($recipe[$slug] ?? 0) * $qty;
                    if ($need <= 0) continue;
                    $stock = WeaponStock::where('slug', $slug)->lockForUpdate()->firstOrFail();
                    if ($stock->quantity < $need) {
                        throw new \RuntimeException("Pas assez de {$stock->name}");
                    }
                    $stock->removeQuantity($need);
                    WeaponStockMovement::create([
                        'weapon_stock_id' => $stock->id,
                        'quantity_change' => -$need,
                        'reason' => 'craft_consume',
                        'user_id' => $userId,
                        'notes' => "Craft {$qty}× {$weapon->name}",
                        'created_at' => now(),
                    ]);
                }

                // Produce finished weapon
                $weaponStock = WeaponStock::where('slug', 'weapon_' . $weapon->slug)->lockForUpdate()->firstOrFail();
                $weaponStock->addQuantity($qty);
                WeaponStockMovement::create([
                    'weapon_stock_id' => $weaponStock->id,
                    'quantity_change' => $qty,
                    'reason' => 'craft_produce',
                    'user_id' => $userId,
                    'notes' => "Craft {$qty}× {$weapon->name}",
                    'created_at' => now(),
                ]);
            });

            Notification::make()
                ->title("{$qty}× {$weapon->name} crafté(s) avec succès")
                ->success()
                ->send();

            $this->weapon_id = null;
            $this->quantity = 1;
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('Craft impossible')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
