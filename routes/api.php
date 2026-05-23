<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConsultationController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;


// Routes publiques
Route::post('/login', [AuthController::class, 'login']);

// Routes authentifiées
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    // ─── Profil ────────────────────────────────────────────────
    Route::get('/profile',          [ProfileController::class, 'show']);
    Route::put('/profile',          [ProfileController::class, 'update']);
    Route::post('/profile/password', [ProfileController::class, 'updatePassword']);
    Route::post('/profile/avatar',  [ProfileController::class, 'uploadAvatar']);



    // ─── Patients (Dentiste + Assistant) ─────────────────────────────
    Route::middleware('assistant')->group(function () {
        Route::get('/patients',              [PatientController::class, 'index']);
        Route::post('/patients',             [PatientController::class, 'store']);
        Route::get('/patients/{patient}',    [PatientController::class, 'show']);
        Route::put('/patients/{patient}',    [PatientController::class, 'update']);

        // Alertes médicales
        Route::post('/patients/{patient}/alerts',           [PatientController::class, 'storeAlert']);
        Route::delete('/patients/{patient}/alerts/{alert}', [PatientController::class, 'destroyAlert']);

        // Documents
        Route::post('/patients/{patient}/documents',              [PatientController::class, 'uploadDocument']);
        Route::delete('/patients/{patient}/documents/{document}', [PatientController::class, 'destroyDocument']);

        // Odontogramme
        Route::get('/patients/{patient}/teeth', [PatientController::class, 'teeth']);

        // Liste des actes pour la modal
        Route::get('/catalog-acts', [AppointmentController::class, 'catalogActs']);

        // RDV du jour
        Route::get('/appointments',              [AppointmentController::class, 'index']);
        Route::post('/appointments',             [AppointmentController::class, 'store']);
        Route::put('/appointments/{appointment}', [AppointmentController::class, 'update']);

        // Changer le statut uniquement
        Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);

        // Annuler
        Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy']);

        // ─── Consultations ─────────────────────────────────────────────

        // Liste + création
        Route::get('/consultations',  [ConsultationController::class, 'index']);
        Route::post('/consultations', [ConsultationController::class, 'store']);

        // Fiche + modification
        Route::get('/consultations/{consultation}',    [ConsultationController::class, 'show']);
        Route::put('/consultations/{consultation}',    [ConsultationController::class, 'update']);

        // Ajouter une séance à une consultation EN_COURS
        Route::post('/consultations/{consultation}/session', [ConsultationController::class, 'addSession']);

        // Clôturer
        Route::patch('/consultations/{consultation}/close', [ConsultationController::class, 'close']);

        // ─── Paiements ─────────────────────────────────────────────────
        Route::get('/payments',     [PaymentController::class, 'index']);
        Route::post('/payments',    [PaymentController::class, 'store']);
        Route::get('/payments/{payment}',    [PaymentController::class, 'show']);
        Route::put('/payments/{payment}',    [PaymentController::class, 'update']);

        // Versements
        Route::post(
            '/payments/{payment}/transactions',
            [PaymentController::class, 'addTransaction']
        );
        Route::delete(
            '/payments/{payment}/transactions/{transaction}',
            [PaymentController::class, 'deleteTransaction']
        );
        // Liste patients avec soldes + stats
        Route::get('/payments', [PaymentController::class, 'index']);

        // Fiche complète d'un patient (consultations + versements)
        Route::get('/payments/{patientId}', [PaymentController::class, 'show']);

        // Ajouter un versement pour un patient
        Route::post(
            '/payments/{patientId}/transactions',
            [PaymentController::class, 'addTransaction']
        );


        Route::get('/dashboard', [DashboardController::class, 'index']);
    });

    // ─── Archivage (Dentiste uniquement) ─────────────────────────────
    Route::middleware('dentist')->group(function () {
        Route::delete('/patients/{patient}',         [PatientController::class, 'destroy']);
        Route::post('/patients/{patient}/restore',   [PatientController::class, 'restore']);
        Route::delete('/consultations/{consultation}', [ConsultationController::class, 'destroy']);
        Route::delete('/payments/{payment}', [PaymentController::class, 'destroy']);
        Route::delete(
            '/payments/transactions/{transactionId}',
            [PaymentController::class, 'deleteTransaction']
        );
    });
});
