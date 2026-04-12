<?php

namespace App\Services;

use App\Models\Event;
use App\Models\RotaAssignment;
use App\Models\RotaRules;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Collection;

class RotaGeneratorService
{
    public function generate(Event $event): array
    {
        $rules = $event->rotaRules ?? new RotaRules([
            'min_shift_minutes' => 120,
            'max_shift_minutes' => 480,
            'min_rest_minutes' => 480,
            'max_shifts_per_volunteer' => 3,
        ]);

        // Get all accepted volunteers for this event
        $volunteers = User::whereHas('applications', function ($q) use ($event) {
            $q->where('event_id', $event->id)->where('status', 'accepted');
        })->get();

        // Get all shifts ordered by start time
        $shifts = $event->shifts()->with('team')->orderBy('starts_at')->get();

        // Validate all shifts against rules
        $invalidShifts = $this->validateShifts($shifts, $rules);

        // Track assignments per volunteer: [user_id => [shift assignments]]
        $assignments = [];
        $volunteerShiftCount = [];

        foreach ($volunteers as $volunteer) {
            $assignments[$volunteer->id] = [];
            $volunteerShiftCount[$volunteer->id] = 0;
        }

        $generatedAssignments = [];
        $warnings = [];

        foreach ($shifts as $shift) {
            $shiftDuration = $shift->starts_at->diffInMinutes($shift->ends_at);

            // Skip shifts that violate duration rules
            if ($shiftDuration < $rules->min_shift_minutes) {
                $warnings[] = "Shift '{$shift->name}' ({$shift->team->name}) is shorter than minimum ({$rules->min_shift_minutes} min). Skipped.";
                continue;
            }
            if ($shiftDuration > $rules->max_shift_minutes) {
                $warnings[] = "Shift '{$shift->name}' ({$shift->team->name}) is longer than maximum ({$rules->max_shift_minutes} min). Skipped.";
                continue;
            }

            // Find eligible volunteers for this shift's team
            $teamMemberIds = $shift->team->members()->pluck('users.id')->toArray();
            $teamVolunteers = $volunteers->filter(fn($v) => in_array($v->id, $teamMemberIds));

            $slotsFilled = 0;
            $maxSlots = $shift->max_volunteers ?? count($teamVolunteers);

            foreach ($teamVolunteers->shuffle() as $volunteer) {
                if ($slotsFilled >= $maxSlots) break;

                // Check max shifts per volunteer
                if ($volunteerShiftCount[$volunteer->id] >= $rules->max_shifts_per_volunteer) {
                    continue;
                }

                // Check rest period conflicts
                if ($this->hasRestConflict($volunteer->id, $shift, $assignments[$volunteer->id], $rules->min_rest_minutes)) {
                    continue;
                }

                // Assign
                $generatedAssignments[] = [
                    'shift_id' => $shift->id,
                    'user_id' => $volunteer->id,
                    'is_manual' => false,
                ];

                $assignments[$volunteer->id][] = $shift;
                $volunteerShiftCount[$volunteer->id]++;
                $slotsFilled++;
            }

            if ($slotsFilled < $shift->min_volunteers) {
                $warnings[] = "Shift '{$shift->name}' ({$shift->team->name}) only filled {$slotsFilled}/{$shift->min_volunteers} minimum slots.";
            }
        }

        return [
            'assignments' => $generatedAssignments,
            'warnings' => $warnings,
        ];
    }

    private function hasRestConflict(int $userId, Shift $newShift, array $assignedShifts, int $minRestMinutes): bool
    {
        foreach ($assignedShifts as $existingShift) {
            $gapAfter = $existingShift->ends_at->diffInMinutes($newShift->starts_at);
            $gapBefore = $newShift->ends_at->diffInMinutes($existingShift->starts_at);

            // Check overlap
            if ($newShift->starts_at->lt($existingShift->ends_at) &&
                $newShift->ends_at->gt($existingShift->starts_at)) {
                return true;
            }

            // Check rest period
            if ($newShift->starts_at->gte($existingShift->ends_at) && $gapAfter < $minRestMinutes) {
                return true;
            }

            if ($existingShift->starts_at->gte($newShift->ends_at) && $gapBefore < $minRestMinutes) {
                return true;
            }
        }
        return false;
    }

    private function validateShifts(Collection $shifts, RotaRules $rules): array
    {
        $invalid = [];
        foreach ($shifts as $shift) {
            $duration = $shift->starts_at->diffInMinutes($shift->ends_at);
            if ($duration < $rules->min_shift_minutes || $duration > $rules->max_shift_minutes) {
                $invalid[] = $shift->id;
            }
        }
        return $invalid;
    }

    public function applyGenerated(Event $event, array $generatedAssignments): void
    {
        // Remove existing auto-generated assignments (keep manual ones)
        RotaAssignment::whereHas('shift', fn($q) => $q->where('event_id', $event->id))
            ->where('is_manual', false)
            ->delete();

        foreach ($generatedAssignments as $assignment) {
            RotaAssignment::firstOrCreate(
                ['shift_id' => $assignment['shift_id'], 'user_id' => $assignment['user_id']],
                ['is_manual' => false]
            );
        }
    }
}
