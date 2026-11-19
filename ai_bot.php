<?php
declare(strict_types=1);

$GROQ_API_KEY = getenv('GROQ_API_KEY') ?: '';
if ($GROQ_API_KEY === '' && file_exists(__DIR__ . '/.env')) {
    $envLines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        if (trim($k) === 'GROQ_API_KEY') {
            $GROQ_API_KEY = trim($v, " \t\n\r\0\x0B\"'");
            break;
        }
    }
}

require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please sign in first.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$input = json_decode((string)file_get_contents('php://input'), true);
$userMessage = trim((string)($input['message'] ?? ''));

if ($userMessage === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Message cannot be empty.']);
    exit;
}

if ($GROQ_API_KEY === '' || $GROQ_API_KEY === 'sddf') {
    echo json_encode(['reply' => 'Please set your valid Groq API key in .env first.']);
    exit;
}

$userId = current_user_id();
$user = current_user();
$pdo = get_db();

$storesStmt = $pdo->prepare('SELECT name FROM stores WHERE user_id = :uid ORDER BY name ASC');
$storesStmt->execute([':uid' => $userId]);
$stores = $storesStmt->fetchAll(PDO::FETCH_COLUMN);

$invStmt = $pdo->prepare(
    'SELECT i.invoice_date, i.amount, i.invoice_type, i.notes, s.name AS store_name
     FROM invoices i
     INNER JOIN stores s ON s.id = i.store_id
     WHERE i.user_id = :uid
     ORDER BY i.invoice_date DESC, i.id DESC
     LIMIT 30'
);
$invStmt->execute([':uid' => $userId]);
$invoices = $invStmt->fetchAll();

$warStmt = $pdo->prepare(
    'SELECT w.product_name, w.start_date, w.end_date, w.duration, s.name AS store_name
     FROM warranties w
     INNER JOIN stores s ON s.id = w.store_id
     WHERE w.user_id = :uid
     ORDER BY w.end_date ASC
     LIMIT 30'
);
$warStmt->execute([':uid' => $userId]);
$warranties = $warStmt->fetchAll();

$contextData = [
    'user_name'  => $user['name'] ?? 'User',
    'today'      => date('Y-m-d'),
    'stores'     => $stores,
    'invoices'   => $invoices,
    'warranties' => $warranties,
];

$systemPrompt = "You are the Invoice Manager AI Assistant. "
    . "You are strictly dedicated to helping the user with their personal stores, invoices, and warranties in Invoice Manager. "
    . "Answer concisely in the same language the user uses (Arabic or English). "
    . "Use ONLY the JSON data below for this user; do not invent any records.\n\n"
    . "User Data:\n" . json_encode($contextData, JSON_UNESCAPED_UNICODE);

$payload = [
    'model'       => 'openai/gpt-oss-20b',
    'temperature' => 0.3,
    'max_tokens'  => 500,
    'messages'    => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userMessage],
    ],
];

$context = stream_context_create([
    'http' => [
        'method'        => 'POST',
        'header'        => "Content-Type: application/json\r\nAuthorization: Bearer " . $GROQ_API_KEY . "\r\n",
        'content'       => json_encode($payload),
        'timeout'       => 20,
        'ignore_errors' => true,
    ],
    'ssl' => [
        'verify_peer'      => false,
        'verify_peer_name' => false,
    ],
]);

$response = @file_get_contents('https://api.groq.com/openai/v1/chat/completions', false, $context);

if ($response === false) {
    echo json_encode(['reply' => 'Sorry, could not reach the AI service right now.']);
    exit;
}

$data = json_decode($response, true);
if (isset($data['error']['message'])) {
    echo json_encode(['reply' => 'AI Error: ' . $data['error']['message']]);
    exit;
}

$reply = $data['choices'][0]['message']['content'] ?? 'No response received.';

echo json_encode(['reply' => $reply], JSON_UNESCAPED_UNICODE);
