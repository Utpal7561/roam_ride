<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\SupportTicket;
use App\Models\Notification;
use App\Helpers\Security;

class SupportController extends Controller {
    private SupportTicket $ticketModel;

    public function __construct() {
        $this->ticketModel = new SupportTicket();
    }

    public function create(Request $request): void {
        $data = $this->validate($request->all(), [
            'customer_name' => 'required|min:2|max:100',
            'customer_phone' => 'required|phone',
            'subject' => 'required|min:3|max:200',
            'message' => 'required|min:5',
            'category' => 'in:booking_issue,payment_issue,vehicle_issue,document_issue,general',
        ]);

        $user = $request->getUser();
        $code = Security::generateTicketCode();

        $ticketId = $this->ticketModel->create([
            'ticket_code' => $code,
            'user_id' => $user ? $user['id'] : null,
            'customer_name' => trim($data['customer_name']),
            'customer_email' => !empty($data['customer_email']) ? trim($data['customer_email']) : null,
            'customer_phone' => trim($data['customer_phone']),
            'booking_id' => !empty($data['booking_id']) ? (int)$data['booking_id'] : null,
            'category' => $data['category'] ?? 'general',
            'subject' => trim($data['subject']),
            'message' => trim($data['message']),
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'open'
        ]);

        $this->success([
            'ticket_id' => $ticketId,
            'ticket_code' => $code
        ], 'Support request submitted successfully. Our team will review and respond shortly!', 201);
    }

    public function index(Request $request): void {
        $user = $request->getUser();
        $isAdmin = $user && in_array($user['role'], ['super_admin', 'admin', 'staff', 'booking_manager']);

        $filters = [];
        if (!$isAdmin) {
            if (!$user) {
                $this->error('Authentication required', null, 401);
            }
            $filters['user_id'] = $user['id'];
        }

        $tickets = $this->ticketModel->getFiltered($filters);
        $this->success($tickets);
    }

    public function reply(Request $request, array $params): void {
        $id = (int)$params['id'];
        $ticket = $this->ticketModel->find($id);

        if (!$ticket) {
            $this->error('Ticket not found', null, 404);
        }

        $admin = $request->getUser();
        $reply = $request->get('admin_reply');
        $status = $request->get('status', 'resolved');

        $this->ticketModel->update($id, [
            'admin_reply' => $reply,
            'status' => $status,
            'assigned_to_user_id' => $admin['id']
        ]);

        if ($ticket['user_id']) {
            Notification::createForUser(
                $ticket['user_id'],
                "Support Update: {$ticket['ticket_code']}",
                "Our team has responded to your inquiry: '{$ticket['subject']}'.",
                'system',
                "/contact"
            );
        }

        $this->success(null, 'Reply sent and ticket status updated.');
    }
}
