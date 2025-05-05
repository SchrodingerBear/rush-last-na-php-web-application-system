<?php 
session_start();

// Require composer autoloader
require_once '../vendor/autoload.php';

// Import PDF Parser
use Smalot\PdfParser\Parser;
// Import PHPOffice/PhpWord
use PhpOffice\PhpWord\IOFactory;

class FileParser {
    private $filename;
    
    public function __construct($filename) {
        $this->filename = $filename;
    }
    
    public function parseFile() {
        $extension = strtolower(pathinfo($this->filename, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'pdf':
                return $this->read_pdf();
            case 'doc':
                return $this->read_doc();
            case 'docx':
                return $this->read_docx();
            default:
                return false;
        }
    }
    
    private function read_pdf() {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($this->filename);
            return $pdf->getText();
        } catch (Exception $e) {
            return "Error extracting PDF content: " . $e->getMessage();
        }
    }
    
    private function read_doc() {
        $fileHandle = fopen($this->filename, "r");
        if (!$fileHandle) {
            return "Error: Unable to open DOC file for reading.";
        }
        
        $line = @fread($fileHandle, filesize($this->filename));   
        fclose($fileHandle);
        
        $lines = explode(chr(0x0D), $line);
        $outtext = "";
        foreach($lines as $thisline) {
            $pos = strpos($thisline, chr(0x00));
            if (($pos !== FALSE) || (strlen($thisline) == 0)) {
                // Do nothing
            } else {
                $outtext .= $thisline . " ";
            }
        }
        $outtext = preg_replace("/[^a-zA-Z0-9\s\,\.\-\n\r\t@\/\_\(\)]/", "", $outtext);
        return $outtext;
    }
    
    private function read_docx() {
        try {
            // Load the document
            $phpWord = IOFactory::load($this->filename);
            
            // Get all sections
            $text = '';
            $sections = $phpWord->getSections();
            
            // Loop through sections
            foreach ($sections as $section) {
                // Get all elements in the section
                $elements = $section->getElements();
                
                // Loop through elements
                foreach ($elements as $element) {
                    // If it's a text run
                    if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                        $text .= $this->extractTextRunContent($element);
                    }
                    // If it's a table
                    elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                        $text .= $this->extractTableContent($element);
                    }
                    // If it's a plain text
                    elseif ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                        $text .= $element->getText() . "\r\n";
                    }
                }
            }
            
            return $text;
        } catch (Exception $e) {
            return "Error extracting DOCX content: " . $e->getMessage();
        }
    }
    
    // Helper method to extract text from TextRun elements
    private function extractTextRunContent($textRun) {
        $text = '';
        $elements = $textRun->getElements();
        
        foreach ($elements as $element) {
            if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                $text .= $element->getText();
            }
        }
        
        return $text . "\r\n";
    }
    
    // Helper method to extract text from Table elements
    private function extractTableContent($table) {
        $text = '';
        $rows = $table->getRows();
        
        foreach ($rows as $row) {
            $cells = $row->getCells();
            $rowText = '';
            
            foreach ($cells as $cell) {
                $elements = $cell->getElements();
                
                foreach ($elements as $element) {
                    if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                        $rowText .= $this->extractTextRunContent($element) . " ";
                    } elseif ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                        $rowText .= $element->getText() . " ";
                    }
                }
            }
            
            $text .= $rowText . "\r\n";
        }
        
        return $text;
    }
}

class Database {
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'academaidb';
    protected $connection;

    public function connect() {
        try {
            $this->connection = new PDO("mysql:host=$this->host;dbname=$this->database", 
                                        $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage(), 3, 'errors.log');
            die("A database connection error occurred. Please try again later.");
        }
        return $this->connection;
    }
}

// ✅ Initialize database connection
$db = new Database();
$pdo = $db->connect();

if (!isset($_SESSION['quiz_taker_id'])) {
    die("Unauthorized access. Please start the quiz again.");
}

$quiz_taker_id = $_SESSION['quiz_taker_id'];

// ✅ Debug: Display quiz_taker_id
echo "✅ Debug: quiz_taker_id from session: $quiz_taker_id<br>";

// ✅ Create upload directory if it doesn't exist
$upload_dir = '../upload';
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        die("❌ Error: Failed to create upload directory.");
    }
    echo "✅ Debug: Upload directory created successfully.<br>";
} else {
    echo "✅ Debug: Upload directory already exists.<br>";
}

try {
    $pdo->beginTransaction();

    // ✅ Step 1: Get all essay questions (essay_id) for the quiz
    $stmt = $pdo->prepare("SELECT eq.essay_id FROM essay_questions eq JOIN quiz_participation qp ON eq.quiz_id = qp.quiz_id WHERE qp.quiz_taker_id = ?");
    $stmt->execute([$quiz_taker_id]);
    $essay_questions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!$essay_questions) {
        die("❌ Debug: No essay questions found for this quiz.");
    }

    // ✅ Debug: Display retrieved essay question IDs
    echo "✅ Debug: Retrieved essay question IDs: " . implode(', ', $essay_questions) . " (Total: " . count($essay_questions) . ")<br>";

    // ✅ Step 2: Insert each answer dynamically
    $index = 0;
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'answer_') === 0) {
            if (!isset($essay_questions[$index])) {
                die("❌ Debug: Error - Missing corresponding essay_id for answer_$index.");
            }

            $question_id = $essay_questions[$index]; // Get the correct essay_id
            $answer_text = trim($value) ?: null;
            $file_name = null;
            $file_path = null;
            $extracted_text = null;

            // ✅ Debug: Display question_id being processed
            echo "✅ Debug: Processing question_id: $question_id for answer_$index.<br>";

            // Handle file upload
            if (!empty($_FILES['input2']['name'][$index])) {
                $file_tmp = $_FILES['input2']['tmp_name'][$index];
                $original_filename = $_FILES['input2']['name'][$index];
                
                // Generate a unique filename to prevent overwrites
                $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
                $unique_filename = uniqid('file_') . '_' . $quiz_taker_id . '_' . $question_id . '.' . $file_extension;
                $file_path = $upload_dir . '/' . $unique_filename;
                
                // Save original filename for reference
                $file_name = $original_filename;
                
                if (is_uploaded_file($file_tmp)) {
                    if (move_uploaded_file($file_tmp, $file_path)) {
                        echo "✅ Debug: File uploaded successfully to: $file_path<br>";
                        
                        // Extract text from file if it's a supported type (pdf, doc, docx)
                        $supported_extensions = ['pdf', 'doc', 'docx'];
                        if (in_array($file_extension, $supported_extensions)) {
                            try {
                                // Create file parser
                                $fileParser = new FileParser($file_path);
                                
                                // Extract text from file
                                $extracted_text = $fileParser->parseFile();
                                
                                if ($extracted_text !== false) {
                                    // Debug: Show extracted text length
                                    echo "✅ Debug: Extracted " . strlen($extracted_text) . " characters from " . strtoupper($file_extension) . " file<br>";
                                    
                                    // Append extracted text to answer_text
                                    if ($answer_text) {
                                        $answer_text .= "\n\n----- EXTRACTED " . strtoupper($file_extension) . " TEXT -----\n\n" . $extracted_text;
                                    } else {
                                        $answer_text = $extracted_text;
                                    }
                                } else {
                                    echo "❌ Debug: Failed to extract text from " . strtoupper($file_extension) . " file<br>";
                                }
                            } catch (Exception $e) {
                                echo "❌ Debug: File parsing error: " . $e->getMessage() . "<br>";
                            }
                        }
                    } else {
                        echo "❌ Debug: Failed to move uploaded file.<br>";
                        $file_name = null;
                        $file_path = null;
                    }
                }
            }

            // ✅ Insert into quiz_answers (now with file_path instead of file_upload)
            $stmt = $pdo->prepare("INSERT INTO quiz_answers (quiz_taker_id, question_id, answer_text, file_upload, file_name) VALUES (:quiz_taker_id, :question_id, :answer_text, :file_path, :file_name)");

            $stmt->execute([
                ':quiz_taker_id' => $quiz_taker_id,
                ':question_id' => $question_id,
                ':answer_text' => $answer_text,
                ':file_path' => $file_path,
                ':file_name' => $file_name
            ]);

            $index++;
        }
    }

    // ✅ Step 3: Update quiz_participation status to 'completed'
 

    echo "<script>alert('Answers submitted successfully!'); </script>";
    //window.location.href = '../user/AcademAI-Animation-Checking.php';
    if(isset($_GET["quiz_id"])&& !empty($_POST)){
        $updateStatus = $pdo->prepare("UPDATE quiz_participation SET status = 'completed' WHERE quiz_taker_id = ?");
        $updateStatus->execute([$quiz_taker_id]);
    
        $pdo->commit();
        $quiz_id = $_GET["quiz_id"];
        header("Location:grade.php?quiz_id=$quiz_id");
    }
   
} catch (PDOException $e) {
    $pdo->rollBack();
    die("Error - " . $e->getMessage());
}
?>