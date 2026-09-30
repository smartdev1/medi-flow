<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\User;

class ExamPolicy
{
    /**
     * Determine whether the user can view any exams.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the exam.
     */
    public function view(User $user, Exam $exam): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->hasRole('medecin', 'pediatre', 'docteur_general')) {
            return $exam->patient->visits()
                ->where('registered_by', $user->id)
                ->exists();
        }

        if ($user->hasRole('laborantin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create exams.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('laborantin', 'medecin', 'pediatre', 'admin');
    }

    /**
     * Determine whether the user can update the exam.
     */
    public function update(User $user, Exam $exam): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->hasRole('laborantin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the exam.
     */
    public function delete(User $user, Exam $exam): bool
    {
        return $user->isAdmin();
    }
}
