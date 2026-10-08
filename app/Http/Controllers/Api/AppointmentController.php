<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Appointment::query()->orderBy('date')->orderBy('time');

        if ($request->query('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->boolean('upcoming')) {
            $query->whereDate('date', '>=', now()->toDateString());
        }

        if ($request->query('month')) {
            // formato esperado: YYYY-MM, usado pela visão de calendário
            $query->whereRaw("to_char(date, 'YYYY-MM') = ?", [$request->query('month')]);
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'description' => 'required|string',
            'address' => 'nullable|string|max:255',
            'total_value' => 'nullable|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0',
            'date' => 'required|date',
            'time' => 'required',
        ]);

        $validated['total_value'] = $validated['total_value'] ?? 0;
        $validated['amount_paid'] = $validated['amount_paid'] ?? 0;
        $this->ensurePaidNotAboveTotal($validated['total_value'], $validated['amount_paid']);

        $appointment = Appointment::create([
            ...$validated,
            'status' => 'pendente',
            'user_id' => $request->user()?->id,
        ]);

        return response()->json($appointment, 201);
    }

    public function update(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'client_name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'address' => 'sometimes|nullable|string|max:255',
            'total_value' => 'sometimes|numeric|min:0',
            'amount_paid' => 'sometimes|numeric|min:0',
            'date' => 'sometimes|date',
            'time' => 'sometimes',
            'status' => 'sometimes|in:pendente,concluido',
        ]);

        $this->ensurePaidNotAboveTotal(
            $validated['total_value'] ?? $appointment->total_value,
            $validated['amount_paid'] ?? $appointment->amount_paid,
        );

        $appointment->update($validated);

        return response()->json($appointment);
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return response()->json(['message' => 'Agendamento excluído.']);
    }

    private function ensurePaidNotAboveTotal($total, $paid): void
    {
        if ((float) $paid > (float) $total) {
            throw ValidationException::withMessages([
                'amount_paid' => 'O valor pago não pode ser maior que o valor total.',
            ]);
        }
    }
}