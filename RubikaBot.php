<?php
class RubikaBot {
    private string $token;
    private string $baseUrl;
    private array $config;
    private array $update = [];
    private array $handlers = [];
    
    public const CHAT_TYPES = ['User', 'Bot', 'Group', 'Channel'];
    public const BUTTON_TYPES = [
        'Simple', 'Selection', 'Calendar', 'NumberPicker',
        'StringPicker', 'Location', 'Payment', 'CameraImage',
        'CameraVideo', 'GalleryImage', 'GalleryVideo', 'File',
        'Audio', 'RecordAudio', 'MyPhoneNumber', 'MyLocation',
        'TextBox', 'Link', 'ActivityPhoneNumber', 'AsMLocation', 'Barcode'
    ];
    
    public function __construct(string $token, array $config = []) {
        $this->token = $token;
        $this->baseUrl = "https://botapi.rubika.ir/v3/{$token}/";
        $this->config = array_merge([
            'timeout' => 30,
            'max_retries' => 3,
            'parse_mode' => 'Markdown'
        ], $config);
        
        $this->captureUpdate();
    }
    
    private function apiRequest(string $method, array $params = []): array {
        $url = $this->baseUrl . $method;
        $retry = 0;
        
        while ($retry < $this->config['max_retries']) {
            try {
                $ch = curl_init($url);
                
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    CURLOPT_POSTFIELDS => json_encode($params),
                    CURLOPT_TIMEOUT => $this->config['timeout']
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                if ($httpCode >= 200 && $httpCode < 300) {
                    return json_decode($response, true) ?? [];
                }
                
                throw new Exception("API Error: HTTP {$httpCode}");
            } catch (Exception $e) {
                $retry++;
                if ($retry === $this->config['max_retries']) {
                    throw $e;
                }
                sleep(1);
            } finally {
                curl_close($ch);
            }
        }
        
        return ['ok' => false, 'error' => 'Request failed'];
    }
    
    public function getMe(): array {
        return $this->apiRequest('getMe');
    }
    public function sendMessage(array $data): array {
        $required = ['chat_id', 'text'];
        $this->validateParams($data, $required);
        
        return $this->apiRequest('sendMessage', $data);
    }

    public function sendFile(
    string $chat_id,
    string $file_path,
    ?string $caption = null,
    ?array $chat_keypad = null,
    ?string $chat_keypad_type = null,
    ?array $inline_keypad = null
    ): array {
        if (!file_exists($file_path)) {
            throw new InvalidArgumentException("File not found: {$file_path}");
        }
        if (filesize($file_path) > 50 * 1024 * 1024) {
            throw new InvalidArgumentException("File size exceeds 50MB limit");
        }
        $mime_type = mime_content_type($file_path);
        $file_type = $this->detectFileType($mime_type);
        $upload_url = $this->requestSendFile($file_type);
        $file_id = $this->uploadFileToUrl($upload_url, $file_path);
        $params = [
            'chat_id' => $chat_id,
            'file_id' => $file_id,
            'type' => $file_type
        ];
    
        if ($caption) {
            $params['text'] = $caption;
        }
    
        if ($chat_keypad && $chat_keypad_type && !$inline_keypad) {
            $params['chat_keypad'] = $chat_keypad;
            $params['chat_keypad_type'] = $chat_keypad_type;
        }
    
        if ($inline_keypad) {
            $params['inline_keypad'] = $inline_keypad;
        }
    
        return $this->apiRequest('sendFile', $params);
    }
    private function detectFileType(string $mime_type): string  
    {  
        $map = [  
            'image/jpeg' => 'Image',  
            'image/png' => 'Image',  
            'image/gif' => 'Gif',  
            'video/mp4' => 'Video',  
            'video/quicktime' => 'Video',  
            'audio/mpeg' => 'Music',  
            'audio/wav' => 'Voice',  
            'application/pdf' => 'File',  
            'application/msword' => 'File',  
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'File',  
            'application/zip' => 'File',  
            'application/x-rar-compressed' => 'File'  
        ];  
      
        return $map[strtolower($mime_type)] ?? 'file';  
    }
    private function requestSendFile(string $type): string
    {
        $validTypes = ['File', 'Image', 'Voice', 'Music', 'Gif', 'Video'];
        if (!in_array($type, $validTypes)) {
            throw new InvalidArgumentException("Invalid file type: {$type}");
        }
        $response = $this->apiRequest('requestSendFile', ['type' => $type]);
        if (!isset($response['status']) || $response['status'] !== 'OK' || empty($response['data']['upload_url'])) {
            throw new RuntimeException("No upload_url returned: " . json_encode($response));
        }
    
        return $response['data']['upload_url'];
    }
    
    private function uploadFileToUrl(string $url, string $file_path): string {
        $mime_type = mime_content_type($file_path);
        $filename = basename($file_path);
    
        $curl_file = new CURLFile($file_path, $mime_type, $filename);
    
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['file' => $curl_file],
            CURLOPT_HTTPHEADER => ['Content-Type: multipart/form-data'],
            CURLOPT_TIMEOUT => 30
        ]);
    
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    
        $data = json_decode($response, true);
    
        if ($http_code !== 200 || !is_array($data)) {
            throw new RuntimeException("Upload failed: HTTP $http_code - " . ($response ?: 'No response'));
        }
    
        if (!isset($data['data']['file_id'])) {
            throw new RuntimeException("No file_id returned from upload: " . json_encode($data));
        }
    
        return $data['data']['file_id'];
    }
    private function getFile($file_id) {
        $res = $this->apiRequest('getFile', ['file_id' => $file_id]);
        return $res['data']['download_url'];
    }
    public function downloadFile($file_id, $to) {
        $url = $this->getFile($file_id);
        file_put_contents($to, file_get_contents($url));
    }
    public function sendPoll(array $data): array {
        $required = ['chat_id', 'question', 'options'];
        $this->validateParams($data, $required);
        
        if (!is_array($data['options']) || count($data['options']) < 2) {
            throw new InvalidArgumentException("Poll must have at least 2 options");
        }
        
        return $this->apiRequest('sendPoll', $data);
    }
    
    public function sendLocation(array $data): array {
        $required = ['chat_id', 'latitude', 'longitude'];
        $this->validateParams($data, $required);
        
        return $this->apiRequest('sendLocation', $data);
    }
    
    public function sendContact(array $data): array {
        $required = ['chat_id', 'first_name', 'phone_number'];
        $this->validateParams($data, $required);
        
        return $this->apiRequest('sendContact', $data);
    }
    
    public function getChat(array $data): array {
        $this->validateParams($data, ['chat_id']);
        
        return $this->apiRequest('getChat', $data);
    }
    
    public function getUpdates(array $data = []): array {
        return $this->apiRequest('getUpdates', $data);
    }
    
    public function forwardMessage(array $data): array {
        $required = ['from_chat_id', 'message_id', 'to_chat_id'];
        $this->validateParams($data, $required);
        
        return $this->apiRequest('forwardMessage', $data);
    }
    
    public function editMessageText(array $data): array {
        $required = ['chat_id', 'message_id', 'text'];
        $this->validateParams($data, $required);
        
        return $this->apiRequest('editMessageText', $data);
    }
    
    public function editMessageKeypad(array $data): array {
        $required = ['chat_id', 'message_id', 'inline_keypad'];
        $this->validateParams($data, $required);
        
        return $this->apiRequest('editMessageKeypad', $data);
    }
    
    public function deleteMessage(array $data): array {
        $this->validateParams($data, ['chat_id', 'message_id']);
        
        return $this->apiRequest('deleteMessage', $data);
    }
    
    public function setCommands(array $data): array {
        $this->validateParams($data, ['bot_commands']);
        
        return $this->apiRequest('setCommands', $data);
    }
    
    public function updateBotEndpoints(array $data): array {
        $required = ['url', 'type'];
        $this->validateParams($data, $required);
        
        return $this->apiRequest('updateBotEndpoints', $data);
    }
    
    public function editChatKeypad(array $data): array {
        $required = ['chat_id', 'chat_keypad_type'];
        $this->validateParams($data, $required);
        
        return $this->apiRequest('editChatKeypad', $data);
    }
    
    public function removeChatKeypad(string $chat_id): array {
        return $this->editChatKeypad([
            'chat_id' => $chat_id,
            'chat_keypad_type' => 'Removed'
        ]);
    }
    
    public function removeInlineKeypad(string $chat_id, string $message_id): array {
        return $this->editMessageKeypad([
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'inline_keypad' => ['rows' => []]
        ]);
    }
    
    public function reply(array $params): array {
        $this->validateParams($params, ['text']);
        $defaults = [
            'text' => '',
            'chat_id' => $this->getChatId(),
            'message_id' => $this->getMessageId(),
            'chat_keypad' => null,
            'inline_keypad' => null,
            'chat_keypad_type' => null,
        ];
        $params = array_merge($defaults, $params);
        
        return $this->sendMessage([
            'chat_id' => $params['chat_id'],
            'text' => $params['text'],
            'reply_to_message_id' => $params['message_id'],
            'inline_keypad' => $params['inline_keypad'],
            'chat_keypad' => $params['chat_keypad'],
            'chat_keypad_type' => $params['chat_keypad_type'],
        ]);
    }
    public function replyFile(array $params): array {
        $this->validateParams($params, ['path']);
        $defaults = [
            'path' => '',
            'caption' => null,
            'chat_id' => $this->getChatId(),
            'inline_keypad' => null,
            'chat_keypad' => null,
            'chat_keypad_type' => null,
        ];
    
        $params = array_merge($defaults, $params);
    
        return $this->sendFile(
            $params['chat_id'],
            $params['path'],
            $params['caption'],
            $params['inline_keypad'] ?? $params['chat_keypad'] ?? null
        );
    }
    public static function makeSimpleButton(string $id, string $text): array {
        return [
            'id' => $id,
            'type' => 'Simple',
            'button_text' => $text
        ];
    }
    
    public static function makeSelectionButton(
        string $id,
        string $title,
        array $items,
        bool $multi = false,
        int $columns = 1
    ): array {
        return [
            'id' => $id,
            'type' => 'Selection',
            'button_text' => $title,
            'button_selection' => [
                'selection_id' => $id,
                'items' => $items,
                'is_multi_selection' => $multi,
                'columns_count' => $columns,
                'title' => $title
            ]
        ];
    }
    
    public static function makeCalendarButton(
        string $id,
        string $title,
        string $type = "DatePersian"
    ): array {
        return [
            'id' => $id,
            'type' => 'Calendar',
            'button_text' => $title,
            'button_calendar' => [
                'type' => $type,
                'title' => $title
            ]
        ];
    }
    
    public static function makeTextboxButton(
        string $id,
        string $title,
        string $lineType = "SingleLine",
        string $keypadType = "String"
    ): array {
        return [
            'id' => $id,
            'type' => 'Textbox',
            'button_text' => $title,
            'button_textbox' => [
                'type_line' => $lineType,
                'type_keypad' => $keypadType,
                'title' => $title
            ]
        ];
    }
    
    public static function makeLocationButton(
        string $id,
        string $title,
        string $type = "Picker"
    ): array {
        return [
            'id' => $id,
            'type' => 'Location',
            'button_text' => $title,
            'button_location' => [
                'type' => $type,
                'title' => $title
            ]
        ];
    }
    
    public static function makeNumberPickerButton(
        string $id,
        string $title,
        string $min,
        string $max,
        ?string $default = null
    ): array {
        return [
            'id' => $id,
            'type' => 'NumberPicker',
            'button_text' => $title,
            'button_number_picker' => [
                'min_value' => $min,
                'max_value' => $max,
                'default_value' => $default,
                'title' => $title
            ]
        ];
    }
    
    public static function makeStringPickerButton(
        string $id,
        string $title,
        array $items,
        ?string $default = null
    ): array {
        return [
            'id' => $id,
            'type' => 'StringPicker',
            'button_text' => $title,
            'button_string_picker' => [
                'items' => $items,
                'default_value' => $default,
                'title' => $title
            ]
        ];
    }
    
    public static function makeKeypadRow(array $buttons): array {
        return ['buttons' => $buttons];
    }
    
    public static function makeKeypad(array $rows, bool $resize = true, bool $onetime = false): array {
        return [
            'rows' => $rows,
            'resize_keyboard' => $resize,
            'on_time_keyboard' => $onetime
        ];
    }
    
    private function captureUpdate(): void {
        $input = file_get_contents("php://input");
        if ($input) {
            $this->update = json_decode($input, true) ?? [];
        }
    }
    
    public function getUpdate(): array {
        return $this->update;
    }
    
    public function getUpdateType(): ?string {
        return $this->update['update']['type'] ?? $this->update['inline_message']['type'] ?? null;
    }
    
    public function getChatId(): ?string {
        return $this->update['update']['chat_id'] ?? $this->update['inline_message']['chat_id'] ?? null;
    }
    
    public function getSenderId(): ?string {
        return $this->update['update']['new_message']['sender_id'] ?? 
               $this->update['inline_message']['sender_id'] ?? null;
    }
    
    public function getText(): ?string {
        return $this->update['update']['new_message']['text'] ?? 
               $this->update['inline_message']['text'] ?? null;
    }
    
    public function getButtonId(): ?string {
        return $this->update['inline_message']['aux_data']['button_id'] ?? null;
    }
    public function getFileName(): ?string {
        return $this->update['update']['new_message']['file']['file_name'] ?? null;
    }
    public function getFileId(): ?string {
        return $this->update['update']['new_message']['file']['file_id'] ?? null;
    }
    public function getFileSize(): ?string {
        return $this->update['update']['new_message']['file']['size'] ?? null;
    }
    public function getMessageId(): ?string {
        return $this->update['update']['new_message']['message_id'] ?? 
               $this->update['inline_message']['message_id'] ?? null;
    }
    public function filterText(?string $match = null): callable {
        return function(RubikaBot $bot) use ($match) {
            $text = $bot->getText();
            if ($text === null) return false;
            return $match === null ? true : trim($text) === trim($match);
        };
    }
    
    public function filterCommand(string $command): callable {
        return function(RubikaBot $bot) use ($command) {
            $text = $bot->getText();
            if ($text === null) return false;
            return strpos(trim('/'.$text), $command) === false;
        };
    }
    public function filterButton(string $button): callable {
        return function(RubikaBot $bot) use ($button) {
            $buttonId = $bot->getButtonId();
            if ($buttonId === null) return false;
            return strpos(trim($buttonId), $button) === false;
        };
    }
    public function filterChatId(string $chat_id): callable {
        return function(RubikaBot $bot) use ($chat_id) {
            $chatId = $bot->getButtonId();
            if ($chatId === null) return false;
            return strpos(trim($chatId), $chat_id) === false;
        };
    }
    public function filterSenderId(string $sender_id): callable {
        return function(RubikaBot $bot) use ($sender_id) {
            $senderId = $bot->getButtonId();
            if ($senderId === null) return false;
            return strpos(trim($senderId), $sender_id) === false;
        };
    }
    public function andFilter(callable ...$filters): callable {
        return function(RubikaBot $bot) use ($filters) {
            foreach ($filters as $f) {
                if (!$f($bot)) return false;
            }
            return true;
        };
    }
    public function orFilter(callable ...$filters): callable {
        return function(RubikaBot $bot) use ($filters) {
            foreach ($filters as $f) {
                if ($f($bot)) return true;
            }
            return false;
        };
    }
    public function onMessage(callable $filter, callable $callback): void {
        if ($filter($this)) {
            $this->handlers[] = fn() => $callback($this);
        }
    }
    public function run(): void {
        foreach ($this->handlers as $handler) {
            $handler(); 
        }
    }
    private function validateParams(array $params, array $required): void {
        foreach ($required as $field) {
            if (!isset($params[$field])) {
                throw new InvalidArgumentException("Missing required parameter: {$field}");
            }
        }
    }
    
    private function validateButtonType(string $type): void {
        if (!in_array($type, self::BUTTON_TYPES)) {
            throw new InvalidArgumentException("Invalid button type: {$type}");
        }
    }
    
    private function validateChatType(string $type): void {
        if (!in_array($type, self::CHAT_TYPES)) {
            throw new InvalidArgumentException("Invalid chat type: {$type}");
        }
    }
}
