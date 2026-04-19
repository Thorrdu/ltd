<?php

namespace App\Http\Controllers;

use App\Models\Cotisation;
use App\Models\McNotification;
use App\Models\McRequest;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private function authUser(Request $request): ?User
    {
        $uid = $request->header('X-Sim-User');
        if (!$uid) return null;
        return User::where('id', $uid)->where('is_active', true)->first();
    }

    /**
     * GET /dashboard/api  —  returns role-appropriate dashboard data
     */
    public function api(Request $request): JsonResponse
    {
        $user = $this->authUser($request);
        if (!$user) return response()->json(['error' => 'Non connecte'], 401);

        $now = Carbon::now();
        $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
        $monthStart = $now->copy()->startOfMonth();

        $data = [
            'alerts' => [],
            'stats'  => [],
        ];

        // ── Every member sees their own stats ────────────────────
        $mySalesWeek = Sale::where('sold_by_user_id', $user->id)
            ->whereBetween('created_at', [$weekStart, $weekEnd])
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total_price),0) as total')
            ->first();

        $data['stats']['mes_ventes_semaine'] = [
            'label' => 'Mes ventes (semaine)',
            'value' => number_format($mySalesWeek->total ?? 0, 0, ',', ' ') . ' $',
            'sub'   => ($mySalesWeek->cnt ?? 0) . ' ventes',
        ];

        // Cotisation status
        $myCot = Cotisation::where('user_id', $user->id)
            ->where('period_start', $weekStart->toDateString())
            ->first();
        if ($myCot) {
            if ($myCot->isPaid()) {
                $data['stats']['ma_cotisation'] = [
                    'label' => 'Cotisation',
                    'value' => 'Payee',
                    'class' => 'positive',
                ];
            } else {
                $remaining = $myCot->remaining();
                $data['stats']['ma_cotisation'] = [
                    'label' => 'Cotisation',
                    'value' => number_format($remaining, 0, ',', ' ') . ' $',
                    'class' => 'warning',
                    'sub'   => 'a payer',
                ];
                $data['alerts'][] = [
                    'type' => 'warning',
                    'icon' => '💰',
                    'text' => 'Cotisation non payee : ' . number_format($remaining, 0, ',', ' ') . ' $',
                    'link' => '/cotisations',
                ];
            }
        }

        // Overdue cotisations
        $overdue = Cotisation::where('user_id', $user->id)
            ->where('period_start', '<', $weekStart->toDateString())
            ->whereRaw('amount_paid < amount_due')
            ->count();
        if ($overdue > 0) {
            $data['alerts'][] = [
                'type' => 'danger',
                'icon' => '⚠️',
                'text' => $overdue . ' cotisation(s) en retard',
                'link' => '/cotisations',
            ];
        }

        // My open attributions count
        $myOpenAttr = StockMovement::where('attributed_to_user_id', $user->id)
            ->where('reason', 'attribution')
            ->whereNull('reconciled_at')
            ->count();
        if ($myOpenAttr > 0) {
            $data['stats']['mes_attributions'] = [
                'label' => 'En possession',
                'value' => $myOpenAttr,
                'sub'   => 'articles',
            ];
        }

        // My pending requests
        $myPendingReq = McRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();
        if ($myPendingReq > 0) {
            $data['stats']['mes_demandes'] = [
                'label' => 'Demandes en cours',
                'value' => $myPendingReq,
                'class' => 'warning',
            ];
        }

        // ── Officer+ sees team stats ──────────────────────────
        if ($user->isAtLeast('officer')) {
            $teamSalesWeek = Sale::whereBetween('created_at', [$weekStart, $weekEnd])
                ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total_price),0) as total')
                ->first();
            $data['stats']['ventes_equipe'] = [
                'label' => 'Ventes MC (semaine)',
                'value' => number_format($teamSalesWeek->total ?? 0, 0, ',', ' ') . ' $',
                'sub'   => ($teamSalesWeek->cnt ?? 0) . ' ventes',
            ];

            // Stock alerts: items with 0 qty in key categories
            $lowStock = StockItem::where('is_active', true)
                ->where('quantity', '<=', 0)
                ->whereIn('category', ['weapon_finished', 'ammo', 'drug'])
                ->count();
            if ($lowStock > 0) {
                $data['alerts'][] = [
                    'type' => 'warning',
                    'icon' => '📦',
                    'text' => $lowStock . ' article(s) en rupture de stock',
                    'link' => '/stocks',
                ];
            }

            // Pending attributions needing approval
            $pendingApprovals = StockMovement::where('requires_approval', true)
                ->whereNull('approved_at')
                ->whereNull('rejected_at')
                ->count();
            if ($pendingApprovals > 0) {
                $data['stats']['validations'] = [
                    'label' => 'Validations att.',
                    'value' => $pendingApprovals,
                    'class' => 'warning',
                ];
            }
        }

        // ── Treasurer sees financial stats ──────────────────
        if ($user->isAtLeast('treasurer')) {
            // Pending reimbursement requests
            $pendingReqs = McRequest::where('status', 'pending')->count();
            if ($pendingReqs > 0) {
                $data['stats']['demandes_pending'] = [
                    'label' => 'Demandes a traiter',
                    'value' => $pendingReqs,
                    'class' => 'warning',
                ];
                $data['alerts'][] = [
                    'type' => 'info',
                    'icon' => '📋',
                    'text' => $pendingReqs . ' demande(s) de remboursement en attente',
                    'link' => '/demandes',
                ];
            }

            // Cash balances
            $argentSale = StockItem::where('slug', 'argent_sale')->value('quantity') ?? 0;
            $argentPropre = StockItem::where('slug', 'argent_propre')->value('quantity') ?? 0;
            $data['stats']['argent_sale'] = [
                'label' => 'Argent sale',
                'value' => number_format($argentSale, 0, ',', ' ') . ' $',
            ];
            $data['stats']['argent_propre'] = [
                'label' => 'Argent propre',
                'value' => number_format($argentPropre, 0, ',', ' ') . ' $',
            ];

            // Unpaid cotisations this week
            $unpaidCot = Cotisation::where('period_start', $weekStart->toDateString())
                ->whereRaw('amount_paid < amount_due')
                ->count();
            if ($unpaidCot > 0) {
                $data['stats']['cotisations_impayees'] = [
                    'label' => 'Cotisations impayees',
                    'value' => $unpaidCot,
                    'class' => 'negative',
                    'sub'   => 'cette semaine',
                ];
            }

            // Revenue this month
            $monthRevenue = Sale::where('created_at', '>=', $monthStart)
                ->sum('total_price');
            $data['stats']['ca_mois'] = [
                'label' => 'CA du mois',
                'value' => number_format($monthRevenue, 0, ',', ' ') . ' $',
            ];
        }

        return response()->json($data);
    }
}
