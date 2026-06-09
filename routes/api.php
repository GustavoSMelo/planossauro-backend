<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\PaymentHistoryController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\PlanningHourController;
use App\Http\Controllers\PlansController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SupportEmailsController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\PaymentHistory\ValidatePaymentHistoryID;
use App\Http\Middleware\Planning\ValidatePlanningID;
use App\Http\Middleware\Subscription\ValidateSubscriptionID;
use App\Http\Middleware\ValidateUserTokenByBody;
use App\Http\Middleware\ValidateUserTokenByBodyUserID;
use App\Http\Middleware\ValidateUserTokenByRoute;
use Illuminate\Support\Facades\Route;

// Health routes
Route::get("", [HealthController::class, "check"])->middleware("throttle:20,1");
Route::get("/health", [HealthController::class, "check"])->middleware(
    "throttle:20,1",
);

// User routes
Route::prefix("user")->group(function () {
    Route::post("", [UserController::class, "store"]);

    Route::get("/{userUUID}", [UserController::class, "show"])
        ->middleware("auth:sanctum")
        ->middleware(ValidateUserTokenByRoute::class);

    Route::put("/{userUUID}", [UserController::class, "update"])
        ->middleware("auth:sanctum")
        ->middleware(ValidateUserTokenByRoute::class);

    Route::delete("/{userUUID}", [UserController::class, "destroy"])
        ->middleware("auth:sanctum")
        ->middleware(ValidateUserTokenByRoute::class);

    Route::post("/resend/validationcode", [
        UserController::class,
        "resendEmail",
    ])
        ->middleware("auth:sanctum")
        ->middleware(ValidateUserTokenByBody::class);

    Route::patch("/validate/{userUUID}", [UserController::class, "validate"])
        ->middleware("auth:sanctum")
        ->middleware(ValidateUserTokenByRoute::class);

    Route::patch("/restore/{userUUID}", [
        UserController::class,
        "removeSoftDeleteUser",
    ])
        ->middleware("auth:sanctum")
        ->middleware(ValidateUserTokenByRoute::class);

    Route::patch("/unlink/{userUUID}", [
        UserController::class,
        "unlinkAccounts",
    ])
        ->middleware("auth:sanctum")
        ->middleware(ValidateUserTokenByRoute::class);
});

// Planning routes
Route::prefix("planning")
    ->middleware("throttle:100,1")
    ->middleware("auth:sanctum")
    ->group(function () {
        Route::post("/search/{userUUID}", [
            PlanningController::class,
            "searchByFilters",
        ])->middleware(ValidateUserTokenByRoute::class);

        Route::get("/paginate/{userUUID}", [
            PlanningController::class,
            "index",
        ])->middleware(ValidateUserTokenByRoute::class);

        Route::post("", [PlanningController::class, "store"])->middleware(
            ValidateUserTokenByBody::class,
        );

        Route::get("/show/{uuid}", [
            PlanningController::class,
            "show",
        ])->middleware(ValidatePlanningID::class);

        Route::put("/{uuid}", [
            PlanningController::class,
            "update",
        ])->middleware(ValidateUserTokenByBodyUserID::class);

        Route::delete("/{uuid}", [
            PlanningController::class,
            "destroy",
        ])->middleware(ValidatePlanningID::class);

        Route::patch("/archive/{uuid}", [
            PlanningController::class,
            "archive",
        ])->middleware(ValidatePlanningID::class);

        Route::patch("/unarchive/{uuid}", [
            PlanningController::class,
            "unarchive",
        ])->middleware(ValidatePlanningID::class);

        Route::post("/create", [PlanningController::class, "create"]);
    });

// PlanningHour routes
Route::prefix("planninghour")
    ->middleware("throttle:100,1")
    ->middleware("auth:sanctum")
    ->group(function () {
        Route::post("", [PlanningHourController::class, "store"])->middleware(
            ValidateUserTokenByBody::class,
        );

        Route::get("/{uuid}", [PlanningHourController::class, "show"]);

        Route::put("/{userUUID}", [
            PlanningHourController::class,
            "update",
        ])->middleware(ValidateUserTokenByRoute::class);

        Route::delete("/{uuid}", [PlanningHourController::class, "destroy"]);
    });

// Plans routes
Route::prefix("plans")
    ->group(function () {
        Route::get("/", [PlansController::class, "index"]);
        Route::get("/{uuid}", [PlansController::class, "show"]);
    })
    ->middleware("throttle:40:1");

// Subscription routes
Route::prefix("subscription")
    ->middleware("throttle:100,1")
    ->middleware("auth:sanctum")
    ->group(function () {
        Route::post("/assign/free/{userUUID}", [
            SubscriptionController::class,
            "assignFreePlanToUser",
        ])->middleware(ValidateUserTokenByRoute::class);

        Route::post("/assign/{userUUID}", [
            SubscriptionController::class,
            "assignPlanToUser",
        ])->middleware(ValidateUserTokenByRoute::class);

        Route::put("/{userUUID}", [
            SubscriptionController::class,
            "assignPlanToUser",
        ])->middleware(ValidateUserTokenByRoute::class);

        Route::patch("/status/update/{subscriptionId}", [
            SubscriptionController::class,
            "patchPlanStatus",
        ])->middleware(ValidateSubscriptionID::class);

        Route::get("/{userUUID}", [
            SubscriptionController::class,
            "show",
        ])->middleware(ValidateUserTokenByRoute::class);

        Route::get("/dashboard/{userUUID}", [
            SubscriptionController::class,
            "dashboard",
        ])->middleware(ValidateUserTokenByRoute::class);

        Route::patch("/{planningType}/{subscriptionId}", [
            SubscriptionController::class,
            "addPlanningUsedOnSubscription",
        ])->middleware(ValidateSubscriptionID::class);

        Route::put("/change/payment/method", [
            SubscriptionController::class,
            "changePaymentMethod",
        ])->middleware(ValidateUserTokenByBodyUserID::class);

        Route::put("/change/subscription/plan", [
            SubscriptionController::class,
            "changeSubscriptionPlan",
        ])->middleware(ValidateUserTokenByBodyUserID::class);

        Route::delete("/cancel/{subscriptionId}", [
            SubscriptionController::class,
            "cancelSubscription",
        ])->middleware(ValidateSubscriptionID::class);
    });

// Payment history routes
Route::prefix("payment/history")
    ->middleware("throttle:100,1")
    ->middleware("auth:sanctum")
    ->group(function () {
        Route::get("/{userUUID}", [
            PaymentHistoryController::class,
            "show",
        ])->middleware(ValidateUserTokenByRoute::class);

        Route::post("/", [
            PaymentHistoryController::class,
            "store",
        ])->middleware(ValidateUserTokenByBody::class);

        Route::put("/{paymentId}", [
            PaymentHistoryController::class,
            "update",
        ])->middleware(ValidateUserTokenByBody::class);

        Route::patch("/upload/nfe/{paymentId}", [
            PaymentHistoryController::class,
            "insertNFe",
        ])->middleware(ValidatePaymentHistoryID::class);

        Route::patch("/status/update/{paymentId}", [
            PaymentHistoryController::class,
            "updatePaymentStatus",
        ])->middleware(ValidatePaymentHistoryID::class);
    });

Route::post("/support/email/{userUUID}", [
    SupportEmailsController::class,
    "createAndSendEmail",
])
    ->middleware("throttle:20,1")
    ->middleware("auth:sanctum")
    ->middleware(ValidateUserTokenByRoute::class);

Route::post("/webhook/payment", [StripeController::class, "handler"]);

// Auth routes
Route::post("/auth/register", [AuthController::class, "register"])->middleware("throttle:20,1");
Route::post("/auth/login", [AuthController::class, "login"])->middleware("throttle:20,1");

Route::get("/token/github/{code}", [
    AuthController::class,
    "githubAccessToken",
])->middleware("throttle:20,1");

Route::get("/auth/github/{token}", [
    AuthController::class,
    "githubAuth",
])->middleware("throttle:20,1");

Route::get("/auth/google/{token}", [
    AuthController::class,
    "googleAuth",
])->middleware("throttle:20,1");

Route::get("/token/facebook/{code}", [
    AuthController::class,
    "facebookAccessToken"
])->middleware("throttle:20,1");

Route::get("/auth/facebook/{token}", [
    AuthController::class,
    'facebookAuth'
])->middleware("throttle:20,1");

Route::delete("/logout/{userUUID}", [AuthController::class, "logout"])
    ->middleware("throttle:10,1")
    ->middleware("auth:sanctum");
