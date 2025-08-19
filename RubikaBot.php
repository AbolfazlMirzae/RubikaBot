<?php

namespace RubikaBot;

class Bot
{
    private string $token;
    private string $baseUrl;
    private array $config = [
        'timeout' => 30,
        'max_retries' => 3,
        'parse_mode' => 'Markdown',
    ];

    private array $update = [];
    private array $chat = [];
    private array $handlers = [];
    private array $handlersWeb = [];
    private array $updateTypes = ['ReceiveUpdate', 'ReceiveInlineMessage', 'ReceiveQuery', 'GetSelectionItem', 'SearchSelectionItems'];
    private ?string $builder_chat_id = null;
    private ?string $builder_text = null;
    private ?string $builder_reply_to = null;
    private ?string $builder_file_path = null;
    private ?string $builder_caption = null;
    private ?string $builder_file_id = null;
    private ?string $builder_file_type = null;
    private ?string $builder_message_id = null;
    private ?string $builder_from_chat_id = null;
    private ?string $builder_to_chat_id = null;
    private ?string $builder_question = null;
    private array  $builder_options = [];
    private ?float  $builder_lat = null;
    private ?float  $builder_lng = null;
    private ?string $builder_contact_first = null;
    private ?string $builder_contact_phone = null;
    private ?array $builder_inline_keypad = null;
    private ?array $builder_chat_keypad = null;
    private ?string $builder_chat_keypad_type = null;
    private array  $lastResponse = [];

    public function __construct(string $token, array $config = [])
    {
        $this->token = $token;
        $this->baseUrl = "https://botapi.rubika.ir/v3/{$token}/";
        $this->config = array_merge($this->config ?? [], $config);
        $this->captureUpdate();
    }
    
    public function chat(string $chat_id): self
    {
        $this->builder_chat_id = $chat_id;
        return $this;
    }

    public function message(string $text): self
    {
        $this->builder_text = $text;
        return $this;
    }

    public function replyTo(string $message_id): self
    {
        $this->builder_reply_to = $message_id;
        return $this;
    }

    public function file(string $path): self
    {
        $this->builder_file_path = $path;
        $this->builder_file_id = null;
        $this->builder_file_type = null;
        return $this;
    }
    public function file_id(string $file_id) {
        $this->builder_file_id = $file_id;
        return $this;
    }
    public function file_type(string $file_type) {
        $this->builder_file_type = $file_type;
        return $this;
    }
    public function caption(string $caption): self
    {
        $this->builder_caption = $caption;
        return $this;
    }

    public function poll(string $question, array $options): self
    {
        $this->builder_question = $question;
        $this->builder_options = $options;
        return $this;
    }

    public function location(float $lat, float $lng): self
    {
        $this->builder_lat = $lat;
        $this->builder_lng = $lng;
        return $this;
    }

    public function contact(string $first_name, string $phone_number): self
    {
        $this->builder_contact_first = $first_name;
        $this->builder_contact_phone = $phone_number;
        return $this;
    }
    public function inlineKeypad(array $keypad): self {
        $this->builder_inline_keypad = $keypad;
        return $this;
    }
    public function chatKeypad(array $keypad, ?string $keypad_type = 'New'): self {
        $this->builder_chat_keypad = $keypad;
        $this->builder_chat_keypad_type = $keypad_type;
        return $this;
    }
    public function forwardFrom(string $from_chat_id): self
    {
        $this->builder_from_chat_id = $from_chat_id;
        return $this;
    }

    public function forwardTo(string $to_chat_id): self
    {
        $this->builder_to_chat_id = $to_chat_id;
        return $this;
    }
    public function messageId(string $message_id): self
    {
        $this->builder_message_id = $message_id;
        return $this;
    }
    private function resetBuilder(): void
    {
        $this->builder_text = null;
        $this->builder_reply_to = null;
        $this->builder_file_path = null;
        $this->builder_caption = null;
        $this->builder_file_id = null;
        $this->builder_file_type = null;
        $this->builder_message_id = null;
        $this->builder_from_chat_id = null;
        $this->builder_to_chat_id = null;
        $this->builder_question = null;
        $this->builder_options = [];
        $this->builder_lat = null;
        $this->builder_lng = null;
        $this->builder_contact_first = null;
        $this->builder_contact_phone = null;
        $this->builder_inline_keypad = null;
        $this->builder_chat_keypad = null;
        $this->builder_chat_keypad_type = null;
    }

    public function send(): array
    {
        if (!$this->builder_chat_id) {
            throw new \InvalidArgumentException("chat_id is required");
        }
        if ($this->builder_text === null) {
            throw new \InvalidArgumentException("text is required for send()");
        }

        $params = [
            'chat_id' => $this->builder_chat_id,
            'text' => $this->builder_text,
        ];
        if ($this->builder_reply_to) {
            $params['reply_to_message_id'] = $this->builder_reply_to;
        }
        if ($this->builder_chat_keypad) {
            $params['chat_keypad'] = $this->builder_chat_keypad;
            $params['chat_keypad_type'] = $this->builder_chat_keypad_type;
        }
        if ($this->builder_inline_keypad) {
            $params['inline_keypad'] = $this->builder_inline_keypad;
        }
        $res = $this->apiRequest('sendMessage', $params);
        $this->lastResponse = $res;
        $this->resetBuilder();
        return $res;
    }

    public function sendFile(): array
    {
        if (!$this->builder_chat_id) {
            throw new \InvalidArgumentException("chat_id is required");
        }
        if (!$this->builder_file_path && !isset($this->builder_file_id)) {
            throw new \InvalidArgumentException("file path is required");
        }
        if (!file_exists($this->builder_file_path) && !isset($this->builder_file_id)) {
            throw new \InvalidArgumentException("File not found: {$this->builder_file_path}");
        }
        if (!isset($this->builder_file_id)) {
            $mime_type = mime_content_type($this->builder_file_path);
            $file_type = $this->detectFileType($mime_type);
            $upload_url = $this->requestSendFile($file_type);
            $file_id = $this->uploadFileToUrl($upload_url, $this->builder_file_path);
        } else {
            $file_type = $this->builder_file_type ?? 'Image';
            $file_id = $this->builder_file_id ?? null;
        }
        // sendFile
        $params = [
            'chat_id' => $this->builder_chat_id,
            'file_id' => $file_id,
            'type' => $file_type,
        ];
        if ($this->builder_reply_to) {
            $params['reply_to_message_id'] = $this->builder_reply_to;
        }
        if ($this->builder_caption) {
            $params['text'] = $this->builder_caption;
        }
        if ($this->builder_chat_keypad) {
            $params['chat_keypad'] = $this->builder_chat_keypad;
            $params['chat_keypad_type'] = $this->builder_chat_keypad_type;
        }
        if ($this->builder_inline_keypad) {
            $params['inline_keypad'] = $this->builder_inline_keypad;
        }
        $res = $this->apiRequest('sendFile', $params);
        $this->lastResponse = $res;
        $this->resetBuilder();
        return ['api' => $res, 'file_id' => $file_id, 'type' => $file_type];
    }
    public function sendPoll(): array
    {
        if (!$this->builder_chat_id) {
            throw new \InvalidArgumentException("chat_id is required");
        }
        if (!$this->builder_question || !is_array($this->builder_options) || count($this->builder_options) < 2) {
            throw new \InvalidArgumentException("Poll requires question and at least 2 options");
        }
        $params = [
            'chat_id' => $this->builder_chat_id,
            'question' => $this->builder_question,
            'options' => $this->builder_options,
        ];
        $res = $this->apiRequest('sendPoll', $params);
        $this->lastResponse = $res;
        $this->resetBuilder();
        return $res;
    }
    public function sendLocation(): array
    {
        if (!$this->builder_chat_id) {
            throw new \InvalidArgumentException("chat_id is required");
        }
        if ($this->builder_lat === null || $this->builder_lng === null) {
            throw new \InvalidArgumentException("latitude and longitude are required");
        }
        $params = [
            'chat_id' => $this->builder_chat_id,
            'latitude' => $this->builder_lat,
            'longitude' => $this->builder_lng,
        ];
        if ($this->builder_reply_to) {
            $params['reply_to_message_id'] = $this->builder_reply_to;
        }
        if ($this->builder_chat_keypad) {
            $params['chat_keypad'] = $this->builder_chat_keypad;
            $params['chat_keypad_type'] = $this->builder_chat_keypad_type;
        }
        if ($this->builder_inline_keypad) {
            $params['inline_keypad'] = $this->builder_inline_keypad;
        }
        $res = $this->apiRequest('sendLocation', $params);
        $this->lastResponse = $res;
        $this->resetBuilder();
        return $res;
    }

    public function sendContact(): array
    {
        if (!$this->builder_chat_id) {
            throw new \InvalidArgumentException("chat_id is required");
        }
        if (!$this->builder_contact_first || !$this->builder_contact_phone) {
            throw new \InvalidArgumentException("first_name and phone_number are required");
        }
        $params = [
            'chat_id' => $this->builder_chat_id,
            'first_name' => $this->builder_contact_first,
            'phone_number' => $this->builder_contact_phone,
        ];
        if ($this->builder_reply_to) {
            $params['reply_to_message_id'] = $this->builder_reply_to;
        }
        if ($this->builder_chat_keypad) {
            $params['chat_keypad'] = $this->builder_chat_keypad;
            $params['chat_keypad_type'] = $this->builder_chat_keypad_type;
        }
        if ($this->builder_inline_keypad) {
            $params['inline_keypad'] = $this->builder_inline_keypad;
        }
        $res = $this->apiRequest('sendContact', $params);
        $this->lastResponse = $res;
        $this->resetBuilder();
        return $res;
    }
    public function forward(): array
    {
        if (!$this->builder_from_chat_id || !$this->builder_message_id || !$this->builder_to_chat_id) {
            throw new \InvalidArgumentException("from_chat_id, message_id and to_chat_id are required for forward()");
        }
        $params = [
            'from_chat_id' => $this->builder_from_chat_id,
            'message_id' => $this->builder_message_id,
            'to_chat_id' => $this->builder_to_chat_id,
        ];
        $res = $this->apiRequest('forwardMessage', $params);
        $this->lastResponse = $res;
        $this->resetBuilder();
        return $res;
    }

    public function sendEditText(): array
    {
        if (!$this->builder_chat_id || !$this->builder_message_id || $this->builder_text === null) {
            throw new \InvalidArgumentException("chat_id, message_id and text are required for edit");
        }
        $params = [
            'chat_id' => $this->builder_chat_id,
            'message_id' => $this->builder_message_id,
            'text' => $this->builder_text,
        ];
        $res = $this->apiRequest('editMessageText', $params);
        $this->lastResponse = $res;
        $this->resetBuilder();
        return $res;
    }
    public function sendEditChatKeypad(): array
    {
        if (!$this->builder_chat_keypad || !$this->builder_chat_id) {
            throw new \InvalidArgumentException("chat keypad or chat id are required for edit chat keypad");
        }
        $params = [
            'chat_id' => $this->builder_chat_id,
            'chat_keypad' => $this->builder_chat_keypad,
            'chat_keypad_type' => $this->builder_chat_keypad_type,
        ];
        $res = $this->apiRequest('editChatKeypad', $params);
        $this->lastResponse = $res;
        $this->resetBuilder();
        return $res;
    }
    public function sendEditInlineKeypad(): array
    {
        if (!$this->builder_inline_keypad || !$this->builder_chat_id || !$this->builder_message_id) {
            throw new \InvalidArgumentException("inline keypad or message_id | chat id are required for edit inline keypad");
        }
        $params = [
            'chat_id' => $this->builder_chat_id,
            'message_id' => $this->builder_message_id,
            'inline_keypad' => $this->builder_inline_keypad,
        ];
        $res = $this->apiRequest('editMessageKeypad', $params);
        $this->lastResponse = $res;
        $this->resetBuilder();
        return $res;
    }
    public function sendDelete(): array
    {
        if (!$this->builder_chat_id || !$this->builder_message_id) {
            throw new \InvalidArgumentException("chat_id and message_id are required for delete");
        }
        $params = [
            'chat_id' => $this->builder_chat_id,
            'message_id' => $this->builder_message_id,
        ];
        $res = $this->apiRequest('deleteMessage', $params);
        $this->lastResponse = $res;
        $this->resetBuilder();
        return $res;
    }
    public function reply(): array
    {
        if (!$this->builder_chat_id) {
            $this->chat($this->getChatId());
        }
        if (!$this->builder_message_id) {
            $this->replyTo($this->getMessageId());
        }
        return $this->send();
    }
    public function replyFile(): array
    {
        if (!$this->builder_chat_id) {
            $this->chat($this->getChatId());
        }
        if (!$this->builder_message_id) {
            $this->replyTo($this->getMessageId());
        }
        return $this->sendFile();
    }
    public function replyContact(): array
    {
        if (!$this->builder_chat_id) {
            $this->chat($this->getChatId());
        }
        if (!$this->builder_message_id) {
            $this->replyTo($this->getMessageId());
        }
        return $this->sendContact();
    }
    public function replyLocation(): array
    {
        if (!$this->builder_chat_id) {
            $this->chat($this->getChatId());
        }
        if (!$this->builder_message_id) {
            $this->replyTo($this->getMessageId());
        }
        return $this->sendLocation();
    }
    private function apiRequest(string $method, array $params = []): array
    {
        $url = $this->baseUrl . $method;
        $retry = 0;

        while ($retry < $this->config['max_retries']) {
            $ch = curl_init($url);
            try {
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    CURLOPT_POSTFIELDS => json_encode($params),
                    CURLOPT_TIMEOUT => $this->config['timeout'],
                ]);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                if ($response === false) {
                    $err = curl_error($ch);
                    throw new \Exception("cURL error: {$err}");
                }

                if ($httpCode >= 200 && $httpCode < 300) {
                    curl_close($ch);
                    return json_decode($response, true) ?? [];
                }

                throw new \Exception("API Error: HTTP {$httpCode} - " . ($response ?: 'No response'));
            } catch (\Exception $e) {
                curl_close($ch);
                $retry++;
                if ($retry === $this->config['max_retries']) {
                    throw $e;
                }
                sleep(1);
            }
        }

        return ['ok' => false, 'error' => 'Request failed'];
    }

    public function getMe(): array
    {
        return $this->apiRequest('getMe');
    }

    public function getChat(array $data): array
    {
        $this->validateParams($data, ['chat_id']);
        $res = $this->apiRequest('getChat', $data);
        $this->chat = $res['data'] ?? [];
        return $res;
    }

    public function getUpdates(array $data = []): array
    {
        return $this->apiRequest('getUpdates', $data);
    }
    public function requestSendFile(string $type): string
    {
        $validTypes = ['File', 'Image', 'Voice', 'Music', 'Gif', 'Video'];
        if (!in_array($type, $validTypes)) {
            throw new \InvalidArgumentException("Invalid file type: {$type}");
        }
        $response = $this->apiRequest('requestSendFile', ['type' => $type]);
        if (!isset($response['status']) || $response['status'] !== 'OK' || empty($response['data']['upload_url'])) {
            throw new \RuntimeException("No upload_url returned: " . json_encode($response));
        }
        return $response['data']['upload_url'];
    }

    private function uploadFileToUrl(string $url, string $file_path): string
    {
        $mime_type = mime_content_type($file_path);
        $filename = basename($file_path);
        $curl_file = new \CURLFile($file_path, $mime_type, $filename);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['file' => $curl_file],
            CURLOPT_HTTPHEADER => ['Content-Type: multipart/form-data'],
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        if ($http_code !== 200 || !is_array($data)) {
            throw new \RuntimeException("Upload failed: HTTP $http_code - " . ($response ?: 'No response'));
        }
        if (!isset($data['data']['file_id'])) {
            throw new \RuntimeException("No file_id returned from upload: " . json_encode($data));
        }
        return $data['data']['file_id'];
    }

    public function getFile(string $file_id): string
    {
        $res = $this->apiRequest('getFile', ['file_id' => $file_id]);
        return $res['data']['download_url'] ?? '';
    }

    public function downloadFile(string $file_id, string $to): void
    {
        $url = $this->getFile($file_id);
        if (!$url) {
            throw new \RuntimeException("Download URL not found for file_id: {$file_id}");
        }
        $content = @file_get_contents($url);
        if ($content === false) {
            throw new \RuntimeException("Failed to download file from: {$url}");
        }
        file_put_contents($to, $content);
    }

    public function setCommands(array $data): array
    {
        $this->validateParams($data, ['bot_commands']);
        return $this->apiRequest('setCommands', $data);
    }

    public function updateBotEndpoints(string $url, string $type): array
    {
        $data = [
            'url' => $url ?? throw new \RuntimeException('set url endpoint'),
            'type' => $type ?? throw new \RuntimeException('set type endpoint')
        ];
        return $this->apiRequest('updateBotEndpoints', $data);
    }
    public function setEndpoint(string $url): array {
        $data = [];
        foreach ($this->updateTypes as $type) {
            $data[] = $this->updateBotEndpoints($url, $type);
        }
        return $data;
    }
    private function detectFileType(string $mime_type): string
    {
        $map = [
            'image/jpeg' => 'Image',
            'image/png' => 'Image',
            'image/gif' => 'Gif',
            'video/mp4' => 'Video',
            'video/quicktime' => 'Video',
            'audio/mpeg' => 'File',
            'audio/wav' => 'File',
            'application/pdf' => 'File',
            'application/msword' => 'File',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'File',
            'application/zip' => 'File',
            'application/x-rar-compressed' => 'File',
        ];
        return $map[strtolower($mime_type)] ?? 'File';
    }

    private function validateParams(array $params, array $required): void
    {
        foreach ($required as $field) {
            if (!isset($params[$field])) {
                throw new \InvalidArgumentException("Missing required parameter: {$field}");
            }
        }
    }
    private function captureUpdate(): void
    {
        $input = @file_get_contents("php://input");
        if ($input) {
            $this->update = json_decode($input, true) ?? [];
        } else {
            $this->update = [];
        }
    }

    public function getUpdate(): array
    {
        return $this->update;
    }

    public function getUpdateType(): ?string
    {
        return $this->update['update']['type'] ?? $this->update['inline_message']['type'] ?? null;
    }

    public function getChatId(): ?string
    {
        return $this->update['update']['chat_id'] ?? $this->update['inline_message']['chat_id'] ?? $thid->update['chat_id'] ?? null;
    }

    public function getSenderId(): ?string
    {
        return $this->update['update']['new_message']['sender_id'] ??
               $this->update['inline_message']['sender_id'] ?? null;
    }

    public function getText(): ?string
    {
        return $this->update['update']['new_message']['text'] ?? $this->update['inline_message']['text'] ?? null;
    }

    public function getButtonId(): ?string
    {
        return $this->update['inline_message']['aux_data']['button_id'] ?? null;
    }

    public function getFileName(): ?string
    {
        return $this->update['update']['new_message']['file']['file_name'] ?? null;
    }

    public function getFileId(): ?string
    {
        return $this->update['update']['new_message']['file']['file_id'] ?? null;
    }

    public function getFileSize(): ?string
    {
        return $this->update['update']['new_message']['file']['size'] ?? null;
    }

    public function getMessageId(): ?string
    {
        return $this->update['update']['new_message']['message_id'] ??
               $this->update['inline_message']['message_id'] ?? $this->builder_message_id ?? null;
    }

    public function getChatType(): ?string
    {
        return $this->chat['chat']['chat_type'] ?? null;
    }

    public function getFirstName(): ?string
    {
        return $this->chat['chat']['first_name'] ?? null;
    }

    public function getUserName(): ?string
    {
        return $this->chat['chat']['username'] ?? null;
    }

    public function filterText(?string $match = null): callable
    {
        return function (Bot $bot) use ($match) {
            $text = $bot->getText();
            if ($text === null) return false;
            return $match === null ? true : trim($text) === trim($match);
        };
    }

    public function filterCommand(string $command): callable
    {
        return function (Bot $bot) use ($command) {
            $text = $bot->getText();
            if (!$text) return false;
            $text = trim($text);
            $command = trim($command);
            return ($text === $command) ||
                   ($text === "/$command") ||
                   (strpos($text, "/$command ") === 0);
        };
    }

    public function filterButton(string $button): callable
    {
        return function (Bot $bot) use ($button) {
            $buttonId = $bot->getButtonId();
            if ($buttonId === null) return false;
            return strpos(trim($buttonId), $button) !== false;
        };
    }

    public function filterChat(ChatType $chat) {
        return function (Bot $bot) use ($chat) {
            $chatType = $bot->getChatType();
            return $chatType === $chat->value;
        };
    }

    public function filterChatId(string $chat_id): callable
    {
        return function (Bot $bot) use ($chat_id) {
            $c = $bot->getChatId();
            if ($c === null) return false;
            return strpos(trim($c), $chat_id) !== false;
        };
    }

    public function filterSenderId(string $sender_id): callable
    {
        return function (Bot $bot) use ($sender_id) {
            $s = $bot->getSenderId();
            if ($s === null) return false;
            return strpos(trim($s), $sender_id) !== false;
        };
    }

    public function andFilter(callable ...$filters): callable
    {
        return function (Bot $bot) use ($filters) {
            foreach ($filters as $f) {
                if (!$f($bot)) return false;
            }
            return true;
        };
    }

    public function orFilter(callable ...$filters): callable
    {
        return function (Bot $bot) use ($filters) {
            foreach ($filters as $f) {
                if ($f($bot)) return true;
            }
            return false;
        };
    }
    public function onMessage(callable $filter, callable $callback): void
    {
        $this->handlers[] = [
            'filter' => $filter,
            'callback' => $callback
        ];
    }

    public function run(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($this->handlers as $handler) {
                if ($handler['filter']($this)) {
                    $handler['callback']($this);
                }
            }
        } else {
            $offset_id = null;
            while (true) {
                try {
                    $params = ['limit' => 100];
                    if ($offset_id) {
                        $params['offset_id'] = $offset_id;
                    }

                    $updates = $this->getUpdates($params);
                    if (empty($updates['data']['updates'])) {
                        sleep(2);
                        continue;
                    }

                    if (isset($updates['data']['next_offset_id'])) {
                        $offset_id = $updates['data']['next_offset_id'];
                    }

                    foreach ($updates['data']['updates'] as $update) {
                        $this->update = ['update' => $update];
                        $this->chat($update['chat_id'] ?? '');
                        $this->getChat(['chat_id' => $this->getChatId()]);

                        foreach ($this->handlers as $handler) {
                            if ($handler['filter']($this)) {
                                $handler['callback']($this);
                            }
                        }
                    }

                    sleep(2);
                } catch (\Exception $e) {
                    error_log("Polling error: " . $e->getMessage());
                    sleep(1);
                }
            }
        }
    }
    public function getLastResponse(): array
    {
        return $this->lastResponse;
    }
}
class Button {
    public string $id;
    public string $type;
    public string $button_text;
    public array $extra = [];
    public function __construct(string $id, string $type, string $button_text) {
        $this->id = $id;
        $this->type = $type;
        $this->button_text = $button_text;
    }

    public static function simple(string $id, string $text): self {
        return new self($id, 'Simple', $text);
    }

    public static function selection(string $id, string $title, array $items, bool $multi = false, int $columns = 1): self {
        $btn = new self($id, 'Selection', $title);
        $btn->extra['button_selection'] = [
            'selection_id' => $id,
            'items' => $items,
            'is_multi_selection' => $multi,
            'columns_count' => $columns,
            'title' => $title,
        ];
        return $btn;
    }

    public static function calendar(string $id, string $title, string $calendarType, ?string $min = '1360', ?string $max = '1404'): self {
        $btn = new self($id, 'Calendar', $title);
        $btn->extra['button_calendar'] = [
            'type' => $calendarType,
            'min_year' => $min,
            'max_year' => $max,
            'title' => $title,
        ];
        return $btn;
    }

    public static function numberPicker(string $id, string $title, int $min, int $max, ?int $default = null): self {
        $btn = new self($id, 'NumberPicker', $title);
        $btn->extra['button_number_picker'] = [
            'min_value' => $min,
            'max_value' => $max,
            'default_value' => $default,
            'title' => $title,
        ];
        return $btn;
    }

    public static function stringPicker(string $id, string $title, array $items, ?string $default = null): self {
        $btn = new self($id, 'StringPicker', $title);
        $btn->extra['button_string_picker'] = [
            'items' => $items,
            'default_value' => $default,
            'title' => $title,
        ];
        return $btn;
    }

    public static function location(string $id, string $title, string $type = 'Picker'): self {
        $btn = new self($id, 'Location', $title);
        $btn->extra['button_location'] = [
            'type' => $type,
            'title' => $title,
        ];
        return $btn;
    }

    public static function payment(string $id, string $title): self {
        return new self($id, 'Payment', $title);
    }

    public static function cameraImage(string $id, string $title): self {
        return new self($id, 'CameraImage', $title);
    }

    public static function cameraVideo(string $id, string $title): self {
        return new self($id, 'CameraVideo', $title);
    }

    public static function galleryImage(string $id, string $title): self {
        return new self($id, 'GalleryImage', $title);
    }

    public static function galleryVideo(string $id, string $title): self {
        return new self($id, 'GalleryVideo', $title);
    }

    public static function file(string $id, string $title): self {
        return new self($id, 'File', $title);
    }

    public static function audio(string $id, string $title): self {
        return new self($id, 'Audio', $title);
    }

    public static function recordAudio(string $id, string $title): self {
        return new self($id, 'RecordAudio', $title);
    }

    public static function myPhoneNumber(string $id, string $title): self {
        return new self($id, 'MyPhoneNumber', $title);
    }

    public static function myLocation(string $id, string $title): self {
        return new self($id, 'MyLocation', $title);
    }

    public static function textBox(string $id, string $title, string $lineType = 'SingleLine', string $keypadType = 'String'): self {
        $btn = new self($id, 'TextBox', $title);
        $btn->extra['button_textbox'] = [
            'type_line' => $lineType,
            'type_keypad' => $keypadType,
            'title' => $title,
        ];
        return $btn;
    }

    public static function link(string $id, string $title, string $type, ButtonLink $link): self {
        $btn = new self($id, 'Link', $title);
        if ($type === ButtonLinkType::URL) {
            $btn->extra['button_link'] = [
                'type' => $type,
                'link_url' => $link->link_url
            ];
        } elseif ($type === ButtonLinkType::JoinChannel) {
            $btn->extra['button_link'] = [
                'type' => $type,
                'joinchannel_data' => $link->joinchannel_data ? [
                    'username' => $link->joinchannel_data->username,
                    'ask_join' => $link->joinchannel_data->ask_join
                ] : null
            ];
        }
        return $btn;
    }

    public static function activityPhoneNumber(string $id, string $title): self {
        return new self($id, 'ActivityPhoneNumber', $title);
    }

    public static function asMLocation(string $id, string $title): self {
        return new self($id, 'AsMLocation', $title);
    }

    public static function barcode(string $id, string $title): self {
        return new self($id, 'Barcode', $title);
    }

    public function toArray(): array {
        $base = [
            'id' => $this->id,
            'type' => $this->type,
            'button_text' => $this->button_text,
        ];
        return array_merge($base, $this->extra);
    }
}

class KeypadRow {
    private array $buttons = [];

    public function add(Button $button): self {
        $this->buttons[] = $button;
        return $this;
    }

    public function toArray(): array {
        $arr = [];
        foreach ($this->buttons as $button) {
            $arr[] = $button->toArray();
        }
        return ['buttons' => $arr];
    }
}

class Keypad {
    private array $rows = [];

    private bool $resize_keyboard = true;
    private bool $on_time_keyboard = false;

    public static function make(): self {
        return new self();
    }

    public function addRow(KeypadRow $row): self {
        $this->rows[] = $row;
        return $this;
    }

    public function row(): KeypadRow {
        $row = new KeypadRow();
        $this->rows[] = $row;
        return $row;
    }

    public function setResize(bool $resize): self {
        $this->resize_keyboard = $resize;
        return $this;
    }

    public function setOnetime(bool $onetime): self {
        $this->on_time_keyboard = $onetime;
        return $this;
    }

    public function toArray(): array {
        $rowsArr = [];
        foreach ($this->rows as $row) {
            $rowsArr[] = $row->toArray();
        }
        return [
            'rows' => $rowsArr,
            'resize_keyboard' => $this->resize_keyboard,
            'on_time_keyboard' => $this->on_time_keyboard,
        ];
    }
}
class ButtonLinkType {
    public const URL = "url";
    public const JoinChannel = "joinchannel";
}
class JoinChannelData {
    public string $username;
    public bool $ask_join;

    public function __construct() {}

    public static function make(string $username, bool $ask_join = true): self {
        $obj = new self();
        $obj->username = $username;
        $obj->ask_join = $ask_join;
        return $obj;
    }
}

class OpenChatData {
    public string $chat_id;

    public function __construct() {}

    public static function make(string $chat_id): self {
        $obj = new self();
        $obj->chat_id = $chat_id;
        return $obj;
    }
}

class ButtonLink {
    public ?string $type = null;
    public ?string $link_url = null;
    public ?JoinChannelData $joinchannel_data = null;
    public ?OpenChatData $open_chat_data = null;

    public function __construct() {}

    public static function make(?string $link_url = null, ?string $type = null, ?JoinChannelData $joinchannel_data = null, ?OpenChatData $open_chat_data = null): self {
        $obj = new self();
        $obj->type = $type;
        $obj->link_url = $link_url;
        $obj->joinchannel_data = $joinchannel_data;
        $obj->open_chat_data = $open_chat_data;
        $obj->normalizeLink();
        return $obj;
    }

    private function normalizeLink(): void {
        if (!$this->link_url) {
            return;
        }

        $mappings = [
            "https://rubika.ir/joing/" => "rubika://g.rubika.ir/",
            "https://rubika.ir/joinc/" => "rubika://c.rubika.ir/",
            "https://rubika.ir/post/"  => "rubika://p.rubika.ir/"
        ];

        foreach ($mappings as $prefix => $deep_prefix) {
            if (strpos($this->link_url, $prefix) === 0) {
                $code = substr($this->link_url, strlen($prefix));
                $this->link_url = $deep_prefix . $code;
                break;
            }
        }
    }
}
enum ChatType: string {
    case USER = 'User';
    case GROUP = 'Group';
    case CHANNEL = 'Channel';
    case BOT = 'Bot';
}