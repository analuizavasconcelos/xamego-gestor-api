<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;

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
            'date' => 'required|date',
            'time' => 'required',
        ]);

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
            'date' => 'sometimes|date',
            'time' => 'sometimes',
            'status' => 'sometimes|in:pendente,concluido',
        ]);

        $appointment->update($validated);

        return response()->json($appointment);
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return response()->json(['message' => 'Agendamento excluído.']);
    }
}