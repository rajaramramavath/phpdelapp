<?php
include("simple_html_dom.php");

// Database configuration
$servername = "sql208.infinityfree.com"; // Change if necessary
$username = "if0_37503789"; // Your database username
$password = "n9yrhd8T6uP1"; // Your database password
$dbname = "if0_37503789_cvrnewsdb"; // Your database name

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to fetch the HTML from a URL with User-Agent
function get_html($url) {
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: MyScraper/1.0\r\n"
        ]
    ]);
    return file_get_html($url, false, $context);
}

// Function to send a message via Telegram
function send_telegram_message($bot_token, $chat_id, $message, $parse_mode = null) {
    $encodedMessage = urlencode($message);
    $url = "https://api.telegram.org/bot$bot_token/sendMessage?chat_id=$chat_id&text=$encodedMessage";
    
    // Add parse_mode if provided
    if ($parse_mode) {
        $url .= "&parse_mode=$parse_mode";
    }

    // Use cURL to send the request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);

    // Error handling
    if (curl_errno($ch)) {
        error_log("cURL error: " . curl_error($ch));
        return false;
    }

    curl_close($ch);
    return json_decode($response, true);
}

// Function to get the last sent message from the database
function get_last_sent_message($conn) {
    $result = $conn->query("SELECT content FROM messages ORDER BY sent_at DESC LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        return $row['content'];
    }
    return '';
}

// Function to save the last sent message to the database
function save_last_sent_message($conn, $message) {
    $stmt = $conn->prepare("INSERT INTO messages (content) VALUES (?)");
    $stmt->bind_param("s", $message);
    $stmt->execute();
    $stmt->close();
}

// Specify the URL and your bot details
$sourceUrl = "https://cvr.ac.in/home4/";
$bot_token = "7762678469:AAGN58MI7UMehQ7CZbe9h-a-JvKIe_m66O8";  // Replace with your bot token
$chat_id = "@cvrnotice";            // Replace with your chat ID

// Get the HTML and fetch the desired content
$html = get_html($sourceUrl);

// Check if HTML was fetched successfully
if ($html) {
    // Fetch the title or a specific element (adjust as necessary)
    $title = $html->find('p.newscontainer', 0)->plaintext;

    $htmlString = $html->find('p.newscontainer', 0)->innertext;

    // Regular expression to match the URL
    preg_match('/href="([^"]+)"/', $htmlString, $matches);

    // Check if a match was found
    if (isset($matches[1])) {
        $url = $matches[1]; // The URL is in the first capturing group
    } else {
        $url = "No URL found.";
    }

    // Prepare the message with quoting style
    $msg = $title . "\n" . "<b>Link:</b> <blockquote>" . $url . "</blockquote>"; // Using <qoute> tags

    // Get the last sent message
    $lastSentMessage = get_last_sent_message($conn);

    // Check if the new message is different from the last sent message
    if ($msg !== $lastSentMessage) {
        // Send the new message with Markdown
        $response = send_telegram_message($bot_token, $chat_id, $msg, 'html');
        
        // Save the new message as the last sent message
        save_last_sent_message($conn, $msg);

        // Print the response from Telegram (optional)
        print_r($response);
    } else {
        echo "No new message to send.";
    }
} else {
    echo "Failed to fetch HTML.";
}

// Close the database connection
phpinfo();
$conn->close();
?>