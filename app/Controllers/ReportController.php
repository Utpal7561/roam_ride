<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Database;

class ReportController extends Controller {
    public function bookings(Request $request): void {
        $pdo = Database::getConnection();
        $format = $request->getQuery('export'); // 'csv' or json

        $sql = "SELECT b.booking_code, b.customer_name, b.customer_phone, v.name as vehicle_name, v.reg_number,
                    b.pickup_datetime, b.return_datetime, b.total_days, b.rental_charge, b.deposit_amount,
                    b.discount_amount, b.tax_amount, b.total_amount, b.amount_paid, b.remaining_balance,
                    b.payment_status, b.booking_status, b.created_at
                FROM bookings b
                LEFT JOIN vehicles v ON b.assigned_vehicle_id = v.id OR b.vehicle_id = v.id
                ORDER BY b.id DESC";
        $data = $pdo->query($sql)->fetchAll();

        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=bookings_report_' . date('Ymd_His') . '.csv');
            $output = fopen('php://output', 'w');
            if (!empty($data)) {
                fputcsv($output, array_keys($data[0]));
                foreach ($data as $row) {
                    fputcsv($output, $row);
                }
            }
            fclose($output);
            exit;
        }

        $this->success($data);
    }

    public function revenue(Request $request): void {
        $pdo = Database::getConnection();
        $format = $request->getQuery('export');

        $sql = "SELECT 
            DATE(payment_date) as payment_date,
            payment_method,
            payment_type,
            COUNT(*) as transactions_count,
            SUM(amount) as total_amount
        FROM payments
        WHERE payment_status = 'successful'
        GROUP BY DATE(payment_date), payment_method, payment_type
        ORDER BY payment_date DESC";
        $data = $pdo->query($sql)->fetchAll();

        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=revenue_report_' . date('Ymd_His') . '.csv');
            $output = fopen('php://output', 'w');
            if (!empty($data)) {
                fputcsv($output, array_keys($data[0]));
                foreach ($data as $row) {
                    fputcsv($output, $row);
                }
            }
            fclose($output);
            exit;
        }

        $this->success($data);
    }

    public function vehicles(Request $request): void {
        $pdo = Database::getConnection();
        $sql = "SELECT v.name, v.brand, v.reg_number, v.daily_rate, v.availability_status,
                    COUNT(b.id) as total_trips,
                    COALESCE(SUM(b.total_amount), 0) as total_earnings
                FROM vehicles v
                LEFT JOIN bookings b ON (b.vehicle_id = v.id OR b.assigned_vehicle_id = v.id) AND b.booking_status NOT IN ('cancelled', 'rejected')
                GROUP BY v.id
                ORDER BY total_earnings DESC";
        $data = $pdo->query($sql)->fetchAll();
        $this->success($data);
    }
}
