<?php

namespace App\Http\Controllers;

use App\Models\Planning;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class PlanningController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param string $userUUID
     */
    public function index(string $userUUID)
    {
        try {
            return Planning::query()
                ->orderBy("updated_at", "desc")
                ->where("user_id", "=", $userUUID)
                ->where("deleted_at", "=", null)
                ->paginate(5, "*");
        } catch (\Exception $e) {
            return response()->json([
                "Error to receive values from database",
                400,
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            if (empty($uuid) || strlen($uuid) <= 3 || $uuid === null) {
                return response()->json(
                    ["error" => "uuid provided was not valid"],
                    400,
                );
            }

            return Planning::query()->where("uuid", "=", $uuid)->first();
        } catch (\Exception $e) {
            return response()->json(
                ["message" => "Planning not founded", "error" => $e],
                400,
            );
        }
    }

    public function searchByFilters(Request $request, string $userUUID)
    {
        try {
            $start_date = $request->input("start_plan");
            $school_name = $request->input("school_name");
            $class_name = $request->input("class_name");
            $planning_type = $request->input("planning_type");
            $archived = $request->input("archived");

            if (!$start_date || empty($start_date) || is_null($start_date)) {
                $start_date = "";
            }
            if (!$school_name || empty($school_name) || is_null($school_name)) {
                $school_name = "";
            }
            if (!$class_name || empty($class_name) || is_null($class_name)) {
                $class_name = "";
            }
            if (
                !$planning_type ||
                empty($planning_type) ||
                is_null($planning_type)
            ) {
                $planning_type = "";
            }
            if (!$archived || empty($archived) || is_null($archived)) {
                $archived = false;
            }

            $plannings = Planning::query()
                ->orderBy("updated_at", "desc")
                ->where("user_id", "=", $userUUID)
                ->where("school_name", "like", "%" . $school_name . "%")
                ->where("class_name", "like", "%" . $class_name . "%")
                ->where("deleted_at", $archived ? "!=" : "=", null)
                ->where("start_plan", "like", "%" . $start_date . "%")
                ->get()
                ->toArray();

            $filter = array_filter($plannings, function ($planning) use (
                $planning_type,
            ) {
                if ($planning_type == "Semanal") {
                    return $planning["start_plan"] !== $planning["end_plan"];
                } elseif ($planning_type == "Diario") {
                    return $planning["start_plan"] === $planning["end_plan"];
                } else {
                    return true;
                }
            });

            return response()->json(array_values($filter));
        } catch (\Exception $err) {
            return response()->json(
                [
                    "message" =>
                        "Not possible to find planning with theses criteries",
                    "error" => $err,
                ],
                400,
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "document_b64" => ["required", "string"],
                "start_plan" => ["required", "date"],
                "end_plan" => ["required", "date", "after_or_equal:start_plan"],
                "school_name" => ["required", "string"],
                "class_name" => ["required", "string"],
                "user_id" => ["required", "string", "uuid"],
            ]);

            if ($validator->fails()) {
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

            $documentb64 = $request->input("document_b64");
            $start_plan = $request->input("start_plan");
            $end_plan = $request->input("end_plan");
            $school_name = $request->input("school_name");
            $class_name = $request->input("class_name");

            $planning = Planning::create([
                "document_b64" => $documentb64,
                "class_name" => $class_name,
                "end_plan" => $end_plan,
                "school_name" => $school_name,
                "start_plan" => $start_plan,
                "user_id" => $user_id,
            ]);

            return response()->json([
                "message" => "Planning created with success!",
                "data" => $planning,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "message" => "malformated planning",
                "error" => $e,
                400,
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $uuid)
    {
        try {
            $request->validate([
                "document_b64" => ["required", "string"],
                "start_plan" => ["required", "date"],
                "end_plan" => ["required", "date", "after_or_equal:start_plan"],
                "school_name" => ["required", "string"],
                "class_name" => ["required", "string"],
                "user_id" => ["required", "string", "uuid"],
            ]);

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

            $documentb64 = $request->input("document_b64");
            $start_plan = $request->input("start_plan");
            $end_plan = $request->input("end_plan");
            $school_name = $request->input("school_name");
            $class_name = $request->input("class_name");

            $planningFinded = Planning::query()
                ->where("uuid", "=", $uuid)
                ->first();

            if (!$planningFinded) {
                return response()->json(
                    [
                        "error" => "Planning not found",
                    ],
                    400,
                );
            }

            $planningFinded->update([
                "document_b64" => $documentb64,
                "class_name" => $class_name,
                "end_plan" => $end_plan,
                "school_name" => $school_name,
                "start_plan" => $start_plan,
            ]);

            return response()->json([
                "message" => "Planning updated with success",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "error" => "Planning malformated or not founded id",
            ]);
        }
    }

    public function archive(string $uuid)
    {
        try {
            $planning = Planning::query()->where("uuid", "=", $uuid)->first();

            if (!$planning) {
                return response()->json(
                    ["message" => "Planning not founded"],
                    400,
                );
            }

            $planning->update([
                "deleted_at" =>
                    Date::now()->year .
                    "-" .
                    Date::now()->month .
                    "-" .
                    Date::now()->day,
            ]);

            return response()->json([
                "message" => "Planning archived with success",
            ]);
        } catch (\Exception $err) {
            return response()->json(
                ["message" => "Planning not founded", "error" => $err],
                400,
            );
        }
    }

    public function unarchive(string $uuid)
    {
        try {
            $planning = Planning::query()->where("uuid", "=", $uuid)->first();

            $planning->update([
                "deleted_at" => null,
            ]);

            return response()->json([
                "message" => "Planning unarchived with success",
            ]);
        } catch (\Exception $err) {
            return response()->json(
                ["message" => "Planning not founded", "error" => $err],
                400,
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            if (empty($uuid) || strlen($uuid) <= 3 || $uuid === null) {
                return response()->json(
                    ["error" => "uuid provided was not valid"],
                    400,
                );
            }

            $planningFinded = Planning::query()
                ->where("uuid", "=", $uuid)
                ->first();
            $planningFinded->delete();

            return response()->json([
                "message" => "Planning deleted with success",
            ]);
        } catch (\Exception $e) {
            return response()->json(
                ["error" => "Planning was not founded"],
                400,
            );
        }
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "prompt" => ["required", "string"],
        ]);

        if ($validator->fails()) {
            return response()->json([
                "message" => "Error on validation",
                "errors" => $validator->errors(),
            ]);
        }

        $prompt = $request->input("prompt");

        $message = $this->callOpenAi($prompt);

        return response()->json([
            "message" => $message,
        ]);
    }

    /**
     * Generate planning by context.
     *
     * Builds the LLM prompt server-side with the same output as the
     * frontend getPrompt/getPromptEN helpers (prompt.ts), then forwards
     * it to OpenAI. Frontend decides local vs backend via VITE_APP_MODE
     * and calls this endpoint when not running locally.
     */
    public function generatePlanByContext(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "qsn" => ["required", "string"],
            "activity" => ["required", "string"],
            "locale" => ["nullable", "string", "max:10"],
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    "message" => "Error on validation",
                    "errors" => $validator->errors(),
                ],
                400,
            );
        }

        $qsn = $request->input("qsn");
        $activity = $request->input("activity");
        $locale = $request->input("locale", "pt-BR");

        $prompt =
            $locale === "pt-BR"
                ? $this->buildContextPrompt($qsn, $activity)
                : $this->buildContextPromptEN($qsn, $activity);

        $message = $this->callOpenAi($prompt);

        return response()->json([
            "message" => $message,
        ]);
    }

    /**
     * Same return as frontend getPrompt(qsn, activity) in prompt.ts.
     */
    private function buildContextPrompt(string $qsn, string $activity): string
    {
        $qsnEncoded = json_encode($qsn, JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
        Contexto (JSON de referência): {$qsnEncoded}
        Atividades: {$activity}

        Voce e um professor educador e esta realizando um planejamento de aula
        Tarefa: Com base nos dados acima, gere um JSON estrito.
        Regras:
        1. 'contextualizacao' deve descrever a integração das atividades e o benefício pedagógico, gere de forma resumida mas que aborde todo o conteúdo das atividades.
        2. 'eixo', 'saber', 'aprendizagem' e 'foco_avaliativo' devem ser Arrays, com um item correspondente para cada atividade fornecida.
        3. 'materiais' deve ser uma string única ou lista com os itens necessários, gere apenas os itens que consegue encontrar no dia a dia.

        (Estrutura) Resposta em JSON esperada:
        {
          "contextualizacao": "string",
          "eixo": ["string"],
          "saber": ["string"],
          "aprendizagem": ["string"],
          "foco_avaliativo": ["string"],
          "materiais": "string"
        }

        Respond ONLY with the JSON object
        PROMPT;
    }

    /**
     * Same return as frontend getPromptEN(qsn, activity) in prompt.ts.
     */
    private function buildContextPromptEN(
        string $qsn,
        string $activity,
    ): string {
        $qsnEncoded = json_encode($qsn, JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
        Reference Context (JSON): {$qsnEncoded}
        Activities: {$activity}

        Role: You are an expert educator creating a weekly lesson plan.
        Task: Based on the data above, generate a strict JSON object.

        Rules:
        1. 'contextualizacao': Summarize the integration of all activities and their pedagogical benefits. Ensure it covers all provided content concisely.
        2. 'eixo', 'saber', 'aprendizagem', and 'foco_avaliativo': Must be Arrays, with one corresponding item for each provided activity.
        3. 'materiais': A single string or list of required items. Include only everyday, easily accessible materials.

        Expected JSON Structure:
        {
          "contextualizacao": "string",
          "eixo": ["string"],
          "saber": ["string"],
          "aprendizagem": ["string"],
          "foco_avaliativo": ["string"],
          "materiais": "string"
        }

        Respond ONLY with the JSON object
        PROMPT;
    }

    private function callOpenAi(string $prompt): ?string
    {
        $response = Http::withHeaders([
            "Content-Type" => "application/json",
            "Authorization" => "Bearer " . config("services.openai.secret"),
        ])
            ->timeout(120)
            ->retry(3, 1000)
            ->post("https://api.openai.com/v1/responses", [
                "model" => config("services.openai.model"),
                "input" => $prompt,
                "reasoning" => [
                    "effort" => "low",
                ],
            ]);

        return $response["output"][1]["content"][0]["text"] ?? null;
    }
}
