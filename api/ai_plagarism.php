<?php
header("Content-Type: application/json");

try {
    // Database Connection
    $pdo = new PDO("mysql:host=localhost;dbname=academaidb", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Copyleaks API Key
    $copyleaks_api_key = "59d24c64-9d4f-42b5-99fe-9aa743cef74b";
    $copyleaks_api_url = "https://api.copyleaks.com/v2/scans/submit"; 

    // Get Essay Text
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["text"], $_POST["quiz_taker_id"], $_POST["question_id"], $_POST["answer_id"])) {
        $text = trim($_POST["text"]); // Trim spaces
        $quiz_taker_id = $_POST["quiz_taker_id"];
        $question_id = $_POST["question_id"];
        $answer_id = $_POST["answer_id"];

        // ---- Find Users with the Same Answer ----
        $stmt = $pdo->prepare("SELECT DISTINCT quiz_taker_id FROM quiz_answers WHERE question_id = ? AND answer_text = ?");
        $stmt->execute([$question_id, $text]);
        $matched_users = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Remove the current quiz taker from copied_users list
        $copied_users = array_diff($matched_users, [$quiz_taker_id]);

        // Convert to JSON if there are copied users
        $copied_users_json = !empty($copied_users) ? json_encode(array_values($copied_users)) : null;

        // ---- Plagiarism & AI Detection (Copyleaks) ----
        $scan_data = json_encode(["text" => $text]);
        $scan_options = [
            "http" => [
                "header" => "Content-Type: application/json\r\nAuthorization: Bearer $copyleaks_api_key",
                "method" => "POST",
                "content" => $scan_data
            ]
        ];

        $scan_response = file_get_contents($copyleaks_api_url, false, stream_context_create($scan_options));
        $scan_result = json_decode($scan_response, true);

        // Extract results (Ensure API response matches these keys)
        $plagiarism_percentage = $scan_result["plagiarism"]["percent"] ?? 0;
        $plagiarized_link = $scan_result["plagiarism"]["source_url"] ?? null;
        $ai_score = $scan_result["ai"]["score"] ?? 0;
        $feedback = $scan_result["ai"]["feedback"] ?? null;

        // ---- Store in plagiarism_report ----
        $stmt = $pdo->prepare("INSERT INTO plagiarism_report (quiz_taker_id, question_id, plagiarism_percentage, plagiarized_link, copied_users, answer_id) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$quiz_taker_id, $question_id, $plagiarism_percentage, $plagiarized_link, $copied_users_json, $answer_id]);

        // ---- Store in ai_report ----
        $stmt = $pdo->prepare("INSERT INTO ai_report (quiz_taker_id, question_id, ai_score, feedback, answer_id) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$quiz_taker_id, $question_id, $ai_score, $feedback, $answer_id]);

        // ---- Return Response ----
        echo json_encode([
            "plagiarism_percentage" => $plagiarism_percentage,
            "plagiarized_link" => $plagiarized_link,
            "copied_users" => $copied_users_json,
            "ai_score" => $ai_score,
            "feedback" => $feedback
        ]);
    } else {
        echo json_encode(["error" => "Invalid request. Required fields are missing."]);
    }
} catch (PDOException $e) {
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["error" => "Error: " . $e->getMessage()]);
}
?>
