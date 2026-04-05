<?php

namespace App\Http\Controllers;

use App\Models\PlanningHour;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlanningHourController extends Controller
{
    public function show(string $uuid)
    {
        try {
            if (empty($uuid) || strlen($uuid) <= 3 || $uuid === null) {
                return response()->json(
                    ["error" => "uuid provided was not valid"],
                    400,
                );
            }

            return PlanningHour::query()->where("user_id", "=", $uuid)->first();
        } catch (\Exception $e) {
            return response()->json(
                [
                    "message" => "PlanningHour not founded",
                    "error" => $e,
                ],
                400,
            );
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "interval_between_classes" => ["sometimes", "string"],
                "initial_hour" => ["sometimes", "string"],
                "user_id" => ["required", "string", "uuid"],
            ]);

            if ($validator->failed()) {
                return response()->json(
                    [
                        "message" => "validation failed",
                        "error" => $validator->errors(),
                    ],
                    400,
                );
            }

            $user_id = $request->input("user_id");

            $user = User::find($user_id);

            if (!$user || empty($user) || is_null($user)) {
                return response()->json(
                    [
                        "error" =>
                            "UUID provided is not associated with any user",
                    ],
                    400,
                );
            }

            $intervalBetweenClasses = $request->input(
                "interval_between_classes",
                "00:30",
            );
            $initialHour = $request->input("initial_hour", "12:00");

            $planningHour = PlanningHour::create([
                "interval_between_classes" => $intervalBetweenClasses,
                "initial_hour" => $initialHour,
                "user_id" => $user_id,
            ]);

            return response()->json([
                "message" => "PlanningHour created with success!",
                "data" => $planningHour,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "message" => "malformated planning hour",
                    "error" => $e,
                ],
                400,
            );
        }
    }

    public function update(Request $request, string $userUUID)
    {
        try {
            $validator = Validator::make($request->all(), [
                "interval_between_classes" => ["required", "string"],
                "initial_hour" => ["required", "string"],
            ]);

            if ($validator->failed()) {
                return response()->json(
                    [
                        "message" => "validation failed",
                        "error" => $validator->errors(),
                    ],
                    400,
                );
            }

            $planningHourFinded = PlanningHour::query()
                ->where("user_id", "=", $userUUID)
                ->first();

            if (!$planningHourFinded) {
                return response()->json(
                    [
                        "error" => "PlanningHour not founded",
                    ],
                    400,
                );
            }

            if ($request->has("user_id")) {
                $user_id = $request->input("user_id");
                $user = User::find($user_id);

                if (!$user || empty($user) || is_null($user)) {
                    return response()->json(
                        [
                            "error" =>
                                "UUID provided is not associated with any user",
                        ],
                        400,
                    );
                }
            }

            $updateData = [];

            if ($request->has("interval_between_classes")) {
                $updateData["interval_between_classes"] = $request->input(
                    "interval_between_classes",
                );
            }

            if ($request->has("initial_hour")) {
                $updateData["initial_hour"] = $request->input("initial_hour");
            }

            if (!empty($updateData)) {
                $planningHourFinded->update($updateData);
            }

            return response()->json([
                "message" => "PlanningHour updated with success",
                "data" => $planningHourFinded,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "error" => "PlanningHour malformated or not founded id",
                    "exception" => $e,
                ],
                400,
            );
        }
    }

    public function destroy(string $uuid)
    {
        try {
            if (empty($uuid) || strlen($uuid) <= 3 || $uuid === null) {
                return response()->json(
                    ["error" => "uuid provided was not valid"],
                    400,
                );
            }

            $planningHourFinded = PlanningHour::query()
                ->where("uuid", "=", $uuid)
                ->first();

            if (!$planningHourFinded) {
                return response()->json(
                    ["error" => "PlanningHour not founded"],
                    400,
                );
            }

            $planningHourFinded->delete();

            return response()->json([
                "message" => "PlanningHour deleted with success",
            ]);
        } catch (\Exception $e) {
            return response()->json(
                ["error" => "PlanningHour was not founded"],
                400,
            );
        }
    }
}
