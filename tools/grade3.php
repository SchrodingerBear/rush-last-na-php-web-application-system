<?php
    $criteriaFormatted = $_GET['plagiarismResult'];
    $decodedData = $_GET['decodedData'];
    $question = $_GET['question'];
    $essayText = $_GET['essay'];
    
    

//echo $criteriaFormatted;
//echo $kiss;
$url = 'https://olraceirdna.pythonanywhere.com/evaluate';

$data = array(
'rubrics_criteria' => $criteriaFormatted,
'level'=> $decodedData["headers"],
'essay' => "Description: " . $question['question'] . "\n" .
    $essayText . 
    '<add also reason,here is additional result' . 
    $plagiarismInfo . 
    '>(Note: if essay is too low and criterita is invalid, return json blank and score, and always strictly response as json format. The JSON structure should follow excatly:
        Re -evaluate the ai score  ,use bert model,gptzero, etc determine the score of ai and human text probability, depends on deepest words patterns , add reason and do not label creator or student because anyone can use as quiz participant,deduct points if necessary 
                        The formula for the total score is:
Total Score = Sum(Criterion Performance Level % × Criterion Weight)
Assign a Rating → Choose a rating for each criterion based on the rubric:

Excellent = 5
Good = 4
Satisfactory = 3
Needs Improvement = 2
Poor = 1
Convert the Weights to Decimal Form:

15% → 0.15
20% → 0.20
Multiply the Rating by the Weight for each criterion.
Example:

Thesis Statement: Rating = 4, Weight = 15% →
4
×
0.15
=
0.6
4×0.15=0.6
Use of Evidence: Rating = 5, Weight = 20% →
5
×
0.20
=
1.0
5×0.20=1.0
Add Up the Weighted Scores:

Final Score
=
0.6
+
1.0
+
.
.
.
=
3.6
Final Score=0.6+1.0+...=3.6
Convert to Percentage:
(
3.6
/
5
)
×
100
=
72
%
(3.6/5)×100=72%
                        {
\"criteria_scores\": {
\"Criteria 1 (Conciseness)\": {
\"score\": [SCORE] //note, it will grade criteria percentage not exceeding to weights of rubrics. for example, o will give only 10% of 20% of criteria 1, apply to all rubrics
\"feedback\": [FEEDBACK],
\"suggestions\": [
[SUGGESTION 1],
[SUGGESTION 2]
]
},
\"Criteria 2 (Conciseness)\": {
\"score\": [SCORE],
\"feedback\": [FEEDBACK],
\"suggestions\": [
[SUGGESTION 1],
[SUGGESTION 2]
]
},
[REPEAT FOR OTHER CRITERIA]
},
//note, always it has overall_weighted_score
\"overall_weighted_score\": [SCORE]//this output is the total of scores of each criteria, example 100% a perfect score because, criteria1 = 10  + criteria 2 =50+criteria3 +criteria4 =40,
\"general_assessment\": {
\"strengths\": [
[STRENGTH 1],
[STRENGTH 2],
[STRENGTH 3]
],
\"areas_for_improvement\": [
[AREA 1],
[AREA 2],
[AREA 3]
]
}
}"
>(Note: The score for each criterion should be a percentage (0-100) OF the criterion\'s weight. For example, if a criterion has a weight of 20% and performance is excellent, the score should be 20 (100% of 20%). If performance is average, the score might be 10 (50% of 20%). 
    make a reference base to the answer of each question essay that put by creator Creator benchmark: (Creator benchmark:'. $kiss.')
    The overall_weighted_score should be the sum of all criteria scores, with a maximum possible value of 100. 
  
strictly follow the format of example output:
also rate if the essay is ai generated or human, use berta model,gptzero or grammarly, add benchmark then add reason why need score deduction

{
  "criteria_scores": {
    "Thesis & Focus (Weight: 20%)": {
      "score": 8, //the thesis and focus criteria has 20% weights but got only 8
      "feedback": "✅ Why [current_level]: [2-3 sentences with specific evidence]** \\n\\n**❌ Why not [higher_level]: [specific missing elements].**\\n\\n**❌ Why not [lower_level]: [what the essay did well to avoid this].**\\n\\n**📚 Creator\'s Benchmark: [specific exemplar or standard].",
      "suggestions": [
        "Develop a clear thesis statement that directly addresses the relationship between love and greed.",
        "Ensure all paragraphs and points directly relate to and support your central thesis."
      ]
    },
    "Criteria 2 (Weight: 30%)": {
      "score": 10, //got only 10% of 30% weight of criteria 2
      "feedback": "✅ Why [current_level]: [2-3 sentences with specific evidence]** \\n\\n**❌ Why not [higher_level]: [specific missing elements].**\\n\\n**❌ Why not [lower_level]: [what the essay did well to avoid this].**\\n\\n**📚 Creator\'s Benchmark: [specific exemplar or standard].",
      "suggestions": [
        "Create an outline before writing to ensure a logical flow of ideas.",
        "Use transition words and phrases to connect ideas between sentences and paragraphs."
      ]
    },
    "Criteria 3 (Weight: 10%)": {
      "score": 6, // got only 6% of 10% weights of criteria 3
      "feedback": "✅ Why [current_level]: [2-3 sentences with specific evidence]** \\n\\n**❌ Why not [higher_level]: [specific missing elements].**\\n\\n**❌ Why not [lower_level]: [what the essay did well to avoid this].**\\n\\n**📚 Creator\'s Benchmark: [specific exemplar or standard].",
      "suggestions": [
        "Include relevant examples, stories, or research to support your points about love and greed.",
        "Analyze your evidence to show how it connects to your thesis statement."
      ]
    },
    "Grammar & Mechanics (Weight: 20%)": {
      "score": 20, // got perfect
      "feedback": "✅ Why [current_level]: [2-3 sentences with specific evidence]** \\n\\n**❌ Why not [higher_level]: [specific missing elements].**\\n\\n**❌ Why not [lower_level]: [what the essay did well to avoid this].**\\n\\n**📚 Creator\'s Benchmark: [specific exemplar or standard].",
      "suggestions": [
        "Use a grammar and spell checker to identify and correct errors.",
        "Seek feedback from a peer or tutor to improve your writing clarity."
      ]
    },
    "Writing Style (Weight: 20%)": {
      "score": 5, //got 5 of 20% weights of criteria 5 Writing Style 
      "feedback": "✅ Why [current_level]: [2-3 sentences with specific evidence]** \\n\\n**❌ Why not [higher_level]: [specific missing elements].**\\n\\n**❌ Why not [lower_level]: [what the essay did well to avoid this].**\\n\\n**📚 Creator\'s Benchmark: [specific exemplar or standard].",
      "suggestions": [
        "Practice using concise and clear language.",
        "Revise sentences to improve clarity and reduce ambiguity."
      ]
    }
  },
  "overall_weighted_score": 49, //because 8+10+6+20+5 of get.
  "general_assessment": {
    "strengths": [
      "This essay provides a solid explanation of how photosynthesis contributes to energy flow in an ecosystem, covering key concepts such as the transformation of light energy into chemical energy and how that energy is passed through the food chain. The overall clarity of the position is strong, and the essay logically organizes the points. The essay could improve by incorporating rhetorical devices to make it more persuasive and emotionally engaging. A stronger persuasive tone and more effective use of rhetorical appeals could elevate the essay’s impact."
    ],
    "areas_for_improvement": [
      "1.	Use Rhetorical Devices: Your essay would greatly benefit from including rhetorical strategies like emotional appeals (pathos) or logical reasoning (logos) to make your points more compelling. Try introducing analogies or vivid language to create a stronger emotional response in the reader.
2.	Transitions and Flow: Although the logical flow is generally present, transitions between ideas could be smoother. Use linking phrases to help guide the reader through your points. For example, after explaining the process of photosynthesis, a better transition could be, "As a result of this process, plants provide essential energy to herbivores, and the entire food web is supported."
3.	Persuasiveness: Consider strengthening your persuasive tone. Use more evocative language to convince the reader why photosynthesis is crucial. Instead of simply stating that ecosystems would collapse without it, explore the emotional consequences of such a collapse—how it would affect all life forms in the ecosystem, adding urgency to the argument.
"
    ]
  }
}
},
"ai_detection": {
"formatted": "AI Generated: 90.37% and Human: 10.63%",
"ai_probability": 90.37403630535582,
"human_probability": 10.62596369464418
},
"plagiarism": {
"assessment": "NEGLIGIBLE",
"color": "blue",
"description": "Likely original content",
"overall_percentage": 15.713212737833373,
"overall_score": 0.15713212737833374,
"sources": [
  {
    "avg_similarity": 0.1159510438260195,
    "color": "blue",
    "description": "Likely original content",
    "link": "https://hellopoetry.com/words/naming/",
    "max_similar_part": 2,
    "max_similarity": 0.1835856336622467,
    "part_similarities": [0.11517382043342501, 0.1835856336622467, 0.04909367738238679],
    "plagiarism_level": "NEGLIGIBLE",
    "title": Naming poems - Hello Poetry"
  },
  {
    "avg_similarity": 0.09330885465997801,
    "color": "blue",
    "description": "Likely original content",
    "link": "https://dokumen.pub/the-labor-of-care-filipina-migrants-and-transnational-families-in-the-digital-age-978-0-252-04172-3-978-0-252-08334-1-978-0-252-05039-8.html",
    "max_similar_part": 2,
    "max_similarity": 0.1501936085718466,
    "part_similarities": [0.09847928389559729, 0.1501936085718466, 0.031253671512490165],
    "plagiarism_level": "NEGLIGIBLE",
    "title": "The Labor of Care: Filipina Migrants and Transnational Families in ..."
  },
  {
    "avg_similarity": 0.010085873987294844,
    "color": "blue",
    "description": "Likely original content",
    "link": "https://www.scribd.com/document/806176769/Gr-12-1st-Sem-Core-Subjects-1",
    "max_similar_part": 1,
    "max_similarity": 0.01922664609431736,
    "part_similarities": [0.01922664609431736, 0.011030975867567173, 0],
    "plagiarism_level": "NEGLIGIBLE",
    "title": "Gr 12 1st Sem Core Subjects (1) | PDF | Tagalog Language ..."
  }
],
"success": true,
"total_parts": 3,
"total_sources_analyzed": 3,
"total_sources_found": 3
},
"plagiarism_sources": [
{
  "url": "https://hellopoetry.com/words/naming/",
  "title": Naming poems - Hello Poetry",
  "similarity": 18.35856336622467
},
{
  "url": "https://dokumen.pub/the-labor-of-care-filipina-migrants-and-transnational-families-in-the-digital-age-978-0-252-04172-3-978-0-252-08334-1-978-0-252-05039-8.html",
  "title": "The Labor of Care: Filipina Migrants and Transnational Families in ...",
  "similarity": 15.01936085718466
},
{
  "url": "https://www.scribd.com/document/806176769/Gr-12-1st-Sem-Core-Subjects-1",
  "title": "Gr 12 1st Sem Core Subjects (1) | PDF | Tagalog Language ...",
  "similarity": 1.922664609431736
}
]
}


additional reference ideas . Persuasiveness & Emotional Appeal (25%)
Student Score: 2 (Needs Improvement)

Percentage: 16.67%

Reason for Students Score: The students response includes a general recognition of social medias benefits but lacks depth and emotional appeal. There is no compelling argument or strong use of logic to persuade the reader.

Explanation of Evaluation:

Advanced (4): The student would effectively use logical and emotional appeals to persuade the reader, convincing them through both reasoning and emotional connection.

Proficient (3): The response would include persuasive elements but with some lapses in emotional or logical depth.

Needs Improvement (2): The students response shows an attempt at persuasion but lacks sufficient depth and clarity.

Warning (1): There are no persuasive elements, making the argument unclear.

Teachers Answer Reference: The creators answer is more persuasive, using both logical reasoning (discussing both positive and negative aspects of social media) and emotional appeal (addressing the impact on mental health). The creators use of reasoning and emotional appeal strengthens the overall argument, making it more convincing and engaging, which is absent in the students response.

Clarity of Position (25%)

Student Score: 2 (Needs Improvement)

Percentage: 16.67%

Reason for Students Score: The student mentions social medias benefits, but the position is unclear and not fully developed. There is no clear stance on whether social media is entirely good or bad, and the response lacks explanation of this position.

Explanation of Evaluation:

Advanced (4): The student presents a strong and well-defined position, supported by logical reasoning and examples.

Proficient (3): The position is stated clearly, but could be elaborated or more assertive.

Needs Improvement (2): The position is weak or unclear, and the argument lacks sufficient development.

Warning (1): The student doesnt express a clear position on the issue.

Teachers Answer Reference: In contrast, the creators answer clearly presents a position, discussing both the benefits and drawbacks of social media, and concludes with a suggestion for responsible use. The creators clear, balanced approach shows a well-developed stance that the students answer lacks. The student would benefit from presenting a more defined position, like the creator.

Use of Rhetorical Devices (25%)

Student Score: 1 (Warning)

Percentage: 4.17%

Reason for Students Score: The students answer does not use any rhetorical devices (such as ethos, pathos, or logos) to enhance the argument. It remains a simple statement without any attempt to engage the reader emotionally or logically.

Explanation of Evaluation:

Advanced (4): The student would effectively use rhetorical devices (such as logos, pathos, and ethos) to support and enhance the argument, making it more persuasive.

Proficient (3): The student would use some rhetorical devices, but not consistently or as effectively.

Needs Improvement (2): The student uses minimal rhetorical devices, making the argument less engaging.

Warning (1): The students response lacks any rhetorical devices, leaving the argument flat and unconvincing.

Teachers Answer Reference: The creators response effectively uses logos (logical reasoning) to discuss the effects of social media and pathos (emotional appeal) to highlight the negative consequences on mental health. These rhetorical devices strengthen the argument and make it more engaging, something that is lacking in the students response.

Logical Organization (25%)

Student Score: 2 (Needs Improvement)

Percentage: 16.67%

Reason for Students Score: The students response consists of a single sentence, making it disjointed and lacking logical flow. Ideas are not developed, and there is no progression of thought or clear structure.

Explanation of Evaluation:

Advanced (4): The student would organize ideas logically, ensuring smooth transitions and a well-developed argument.

Proficient (3): The response would show minor lapses in logical flow, but the overall structure would be clear.

Needs Improvement (2): The response lacks clear organization, with ideas presented in a disjointed manner, making it hard to follow.

Warning (1): The students response lacks any logical structure, and the ideas are presented randomly without progression.

Teachers Answer Reference: The creators answer is well-organized, with a clear introduction, followed by the discussion of both benefits and drawbacks of social media, and a conclusion with advice on responsible use. The creator uses clear transitions between ideas, ensuring a logical flow. In contrast, the students response lacks this structure, making the argument difficult to follow and incomplete.

Overall Evaluation
Final Score: 53.34%
                        )'
);

// Initialize cURL session for evaluation
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
'Content-Type: application/json',
'Content-Length: ' . strlen(json_encode($data))
));

// Execute the cURL request
$response = curl_exec($ch);

// Check for errors
if(curl_errno($ch)) {
$evaluationResults[] = [
    'student_id' => $answer['quiz_taker_id'],
    'answer_id' => $answer['answer_id'],
    'error' => curl_error($ch)
];
} else {
// Decode the JSON response
$evaluationResult = json_decode($response, true);
//var_dump($evaluationResult);

// Check if the evaluation exists in the expected structure
if (isset($evaluationResult['evaluation'])) {
// The evaluation field contains a JSON string with triple backticks
$rawEvaluation = $evaluationResult['evaluation'];

// Remove the backticks and extract the JSON part
$matches = [];
if (preg_match('/```json\s*(.*?)\s*```/s', $rawEvaluation, $matches)) {
    $jsonData = $matches[1];
    $decodedEvaluation = json_decode($jsonData, true);
    
    // Now extract the ai_detection data if it exists
    // if (isset($decodedEvaluation['ai_detection'])) {
    //     $aiResult = $decodedEvaluation['ai_detection'];
    // }
}
} else if (isset($evaluationResult['results']) && is_array($evaluationResult['results'])) {
// Alternative structure - direct access to results
// foreach ($evaluationResult['results'] as $result) {
//     if (isset($result['ai_probability']) && isset($result['human_probability'])) {
//         $aiResult = [
//             'ai_probability' => $result['ai_probability'],
//             'human_probability' => $result['human_probability'],
//             'formatted' => "AI Generated: {$result['ai_probability']}% and Human: {$result['human_probability']}%"
//         ];
//         break; // Use the first result that has the data
//     }
// }
}


// Save results to the database
$saveResult = saveEvaluationToDatabase(
    $conn, 
    $answer, 
    $evaluationResult, 
    $aiResult, 
    $plagiarismResult,
    $essayText,
    $quiz_id
);

// Store result for reporting
$evaluationResults[] = [
    'student_id' => $answer['quiz_taker_id'],
    'student_name' => $answer['first_name'] . ' ' . $answer['last_name'],
    'answer_id' => $answer['answer_id'],
    'evaluation_id' => $saveResult['success'] ? $saveResult['id'] : null,
    'success' => $saveResult['success'],
    'message' => $saveResult['message'],
    'overall_score' => isset($evaluationResult['overall_weighted_score']) ? $evaluationResult['overall_weighted_score'] : 0,
    'ai_probability' => isset($aiResult['ai_probability']) ? $aiResult['ai_probability'] : 0,
    'human_probability' => isset($aiResult['human_probability']) ? $aiResult['human_probability'] : 0,
    'plagiarism_score' => isset($plagiarismResult['overall_percentage']) ? $plagiarismResult['overall_percentage'] : 0
];
}
function saveEvaluationToDatabase($conn, $answer, $evaluationResult, $aiResult, $plagiarismResult, $essayText, $quiz_id) {
    try {
        // Check if evaluation already exists
        $checkStmt = $conn->prepare("SELECT evaluation_id FROM essay_evaluations WHERE answer_id = ?");
        $checkStmt->execute([$answer['answer_id']]);
        $existingEval = $checkStmt->fetch(PDO::FETCH_ASSOC);

        // Extract AI and Human probability
        $aiProbability = 0;
        $humanProbability = 0;
        
        if (is_array($aiResult) && isset($aiResult['ai_probability'])) {
            $aiProbability = floatval($aiResult['ai_probability']);
            $humanProbability = floatval($aiResult['human_probability']);
        } else if (!is_array($aiResult)) {
            // Try to extract from string format
            preg_match('/AI Generated: ([\d.]+)%/', $aiResult, $aiMatches);
            preg_match('/Human: ([\d.]+)%/', $aiResult, $humanMatches);
            $aiProbability = isset($aiMatches[1]) ? floatval($aiMatches[1]) : 0;
            $humanProbability = isset($humanMatches[1]) ? floatval($humanMatches[1]) : 0;
        }
        
        // Extract plagiarism score and sources
        $plagiarismScore = isset($plagiarismResult['overall_percentage']) ? 
            floatval($plagiarismResult['overall_percentage']) : 0;
            
        // Extract plagiarism sources/links
        $plagiarismSources = [];
        if (isset($plagiarismResult['sources']) && is_array($plagiarismResult['sources'])) {
            foreach ($plagiarismResult['sources'] as $source) {
                if (isset($source['link']) && isset($source['title']) && isset($source['max_similarity'])) {
                    $plagiarismSources[] = [
                        'url' => $source['link'],
                        'title' => $source['title'],
                        'similarity' => $source['max_similarity'] * 100
                    ];
                }
            }
        }
        
        // Extract overall score from evaluation result
        $overallScore = isset($evaluationResult['overall_weighted_score']) ? 
            floatval($evaluationResult['overall_weighted_score']) : 0;
        
        // Store the evaluation data as JSON
        $evaluationData = json_encode([
            'evaluation' => $evaluationResult,
            'ai_detection' => is_array($aiResult) ? $aiResult : ['formatted' => $aiResult],
            'plagiarism' => $plagiarismResult,
            'plagiarism_sources' => $plagiarismSources
        ]);
        
        // Check plagiarism using the alternative endpoint if no sources are found
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
                $sourcesJson = json_encode($plagiarismSources);
            }
            }
            // var_dump($plagiarismResponse);  
            curl_close($ch);
        }

        // Prepare plagiarism sources as JSON
        $sourcesJson = json_encode($plagiarismSources);
        if ($existingEval) {
            // Update existing evaluation
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
            $aiResult['explanation'],
            $answer['answer_id']
            ]);
            
            return [
            'success' => $success, 
            'message' => 'Evaluation updated successfully', 
            'id' => $existingEval['evaluation_id']
            ];
        } else {
          
            // Insert new evaluation
            $insertStmt = $conn->prepare("
            INSERT INTO essay_evaluations 
            (answer_id, student_id, question_id, quiz_id, overall_score, ai_probability, 
            human_probability, plagiarism_score, plagiarism_sources, 
            evaluation_data, evaluation_date, ai_explain) 
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
            $aiResult['explanation']
            ]);
            
            return [
            'success' => $success, 
            'message' => 'Evaluation saved successfully', 
            'id' => $conn->lastInsertId()
            ];
        }
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}
?>