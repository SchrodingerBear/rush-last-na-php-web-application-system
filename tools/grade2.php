<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../classes/connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo "<h2>Debug Start</h2>";

    if (isset($_SESSION['saveResult'])) {
        $saveResult = $_SESSION['saveResult'];

        echo "<h3>Session Data</h3><pre>";
        var_dump($saveResult);
        echo "</pre>";

        $answer = isset($saveResult['answer']) ? json_decode($saveResult['answer'], true) : null;
        $evaluationResult = isset($saveResult['evaluation']) ? json_decode($saveResult['evaluation'], true) : null;
        $aiResult = isset($saveResult['ai']) ? json_decode($saveResult['ai'], true) : null;
        $plagiarismResult = isset($saveResult['plagiarism']) ? json_decode($saveResult['plagiarism'], true) : null;
        $essayText = isset($saveResult['essay']) ? $saveResult['essay'] : '';
        $quiz_id = isset($saveResult['quiz_id']) ? intval($saveResult['quiz_id']) : 0;

        echo "<h3>Decoded Variables</h3><pre>";
        var_dump([
            'answer' => $answer,
            'evaluationResult' => $evaluationResult,
            'aiResult' => $aiResult,
            'plagiarismResult' => $plagiarismResult,
            'essayText' => $essayText,
            'quiz_id' => $quiz_id
        ]);
        echo "</pre>";

        unset($_SESSION['saveResult']);

        try {
            $db = new Database();
            $conn = $db->connect();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check for existing evaluation
            $checkStmt = $conn->prepare("SELECT evaluation_id FROM essay_evaluations WHERE answer_id = ?");
            $checkStmt->execute([$answer['answer_id']]);
            $existingEval = $checkStmt->fetch(PDO::FETCH_ASSOC);

            $aiProbability = 0;
            $humanProbability = 0;

            if (is_array($aiResult) && isset($aiResult['ai_probability'])) {
                $aiProbability = floatval($aiResult['ai_probability']);
                $humanProbability = floatval($aiResult['human_probability']);
            } else if (!is_array($aiResult)) {
                preg_match('/AI Generated: ([\d.]+)%/', $aiResult, $aiMatches);
                preg_match('/Human: ([\d.]+)%/', $aiResult, $humanMatches);
                $aiProbability = isset($aiMatches[1]) ? floatval($aiMatches[1]) : 0;
                $humanProbability = isset($humanMatches[1]) ? floatval($humanMatches[1]) : 0;
            }

            $plagiarismScore = isset($plagiarismResult['overall_percentage']) ? floatval($plagiarismResult['overall_percentage']) : 0;

            $plagiarismSources = [];
            if (isset($plagiarismResult['sources']) && is_array($plagiarismResult['sources'])) {
                foreach ($plagiarismResult['sources'] as $source) {
                    if (isset($source['link'], $source['title'], $source['max_similarity'])) {
                        $plagiarismSources[] = [
                            'url' => $source['link'],
                            'title' => $source['title'],
                            'similarity' => $source['max_similarity'] * 100
                        ];
                    }
                }
            }

            // If no sources found, use fallback API
            if (empty($plagiarismSources)) {
                $plagiarismApiUrl = 'https://olraceirdna.pythonanywhere.com/check_plagiarism';
                $plagiarismPayload = json_encode(['text' => $essayText]);

                $ch = curl_init($plagiarismApiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $plagiarismPayload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($plagiarismPayload)
                ]);

                $plagiarismResponse = curl_exec($ch);
                if (!curl_errno($ch)) {
                    $plagiarismData = json_decode($plagiarismResponse, true);
                    if (isset($plagiarismData['plagiarism_score'])) {
                        $plagiarismScore = floatval($plagiarismData['plagiarism_score']);
                        $plagiarismSources = $plagiarismData['sources'] ?? [];
                    }
                }
                curl_close($ch);
            }

            $sourcesJson = json_encode($plagiarismSources);
            $overallScore = isset($evaluationResult['overall_weighted_score']) ? floatval($evaluationResult['overall_weighted_score']) : 0;

            $evaluationData = json_encode([
                'evaluation' => $evaluationResult,
                'ai_detection' => is_array($aiResult) ? $aiResult : ['formatted' => $aiResult],
                'plagiarism' => $plagiarismResult,
                'plagiarism_sources' => $plagiarismSources
            ]);

            echo "<h3>Prepared Values for DB</h3><pre>";
            var_dump([
                'answer_id' => $answer['answer_id'],
                'quiz_taker_id' => $answer['quiz_taker_id'],
                'question_id' => $answer['question_id'],
                'quiz_id' => $quiz_id,
                'overallScore' => $overallScore,
                'aiProbability' => $aiProbability,
                'humanProbability' => $humanProbability,
                'plagiarismScore' => $plagiarismScore,
                'sourcesJson' => $sourcesJson,
                'evaluationData' => $evaluationData,
                'ai_explain' => $aiResult['explanation'] ?? null
            ]);
            echo "</pre>";

            if ($existingEval) {
                $updateStmt = $conn->prepare("
                    UPDATE essay_evaluations 
                    SET overall_score = ?, ai_probability = ?, human_probability = ?, 
                        plagiarism_score = ?, plagiarism_sources = ?, 
                        evaluation_data = ?, evaluation_date = NOW(), quiz_id = ?, ai_explain = ?
                    WHERE answer_id = ?
                ");
                $success = $updateStmt->execute([
                    $overallScore,
                    $aiProbability,
                    $humanProbability,
                    $plagiarismScore,
                    $sourcesJson,
                    $evaluationData,
                    $quiz_id,
                    $aiResult['explanation'] ?? null,
                    $answer['answer_id']
                ]);
                echo "<h3>Update Statement Result</h3><pre>";
                var_dump($updateStmt->errorInfo(), $success);
                echo "</pre>";
            } else {
                $insertStmt = $conn->prepare("
                    INSERT INTO essay_evaluations 
                    (answer_id, student_id, question_id, quiz_id, overall_score, ai_probability, 
                     human_probability, plagiarism_score, plagiarism_sources, evaluation_data, 
                     evaluation_date, ai_explain) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
                ");
                $success = $insertStmt->execute([
                    $answer['answer_id'],
                    $answer['quiz_taker_id'],
                    $answer['question_id'],
                    $quiz_id,
                    $overallScore,
                    $aiProbability,
                    $humanProbability,
                    $plagiarismScore,
                    $sourcesJson,
                    $evaluationData,
                    $aiResult['explanation'] ?? null
                ]);
                echo "<h3>Insert Statement Result</h3><pre>";
                var_dump($insertStmt->errorInfo(), $success);
                echo "</pre>";
            }

        } catch (PDOException $e) {
            echo "<h3>PDOException</h3><pre>";
            var_dump($e->getMessage());
            echo "</pre>";
        }
    } else {
        echo "<h3 style='color: red;'>No data found in session.</h3>";
    }
} else {
    echo "<h3>Invalid request method.</h3>";
}

// header("Location:../user/AcademAI-user(learners)-view-quiz-answer-1.php?quiz_id=$quiz_id");
// exit;