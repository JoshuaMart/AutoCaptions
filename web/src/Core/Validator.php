<?php

declare(strict_types=1);

namespace App\Core;

// Placeholder for Application class if not loaded by autoloader
// This is to ensure the Validator can access configurations.
if (!class_exists('App\Core\Application')) {
    class Application {
        private static $instance;
        public static function getInstance() {
            if (null === static::$instance) {
                static::$instance = new self();
            }
            return static::$instance;
        }
        public function getConfig(string $key, $default = null) {
            // Simplified mock config for Validator context
            if ($key === 'security.upload_security') {
                return [
                    'allowed_mime_types' => ['image/jpeg', 'image/png', 'video/mp4'],
                    'max_file_size_bytes' => 50 * 1024 * 1024, // 50MB
                    'sanitize_filenames' => true,
                ];
            }
            if ($key === 'security.validation.stop_on_first_failure') {
                return false;
            }
            return $default;
        }
    }
}

class Validator
{
    protected mixed $value;
    protected string $label;
    protected array $rules = [];
    protected array $errors = [];
    protected bool $isFileType = false;
    protected array $fileData = []; // To store the original $_FILES entry

    protected array $errorMessages = [
        'required' => 'The :label field is required.',
        'email' => 'The :label field must be a valid email address.',
        'minLength' => 'The :label field must be at least :min characters.',
        'maxLength' => 'The :label field must not exceed :max characters.',
        'numeric' => 'The :label field must be a number.',
        'in' => 'The selected :label is invalid.',
        'file.required' => 'The :label field is required.',
        'file.uploadError' => 'The file :label could not be uploaded (error code: :errorCode).',
        'file.maxSize' => 'The file :label must not exceed :maxSize.',
        'file.allowedTypes' => 'The file :label must be one of the following types: :types.',
        'file.validFile' => 'The :label field must be a valid file upload.',
    ];

    protected bool $stopOnFirstFailure;

    protected function __construct(mixed $value, string $label)
    {
        $this->value = $value;
        $this->label = $label;
        $app = Application::getInstance();
        $this->stopOnFirstFailure = $app->getConfig('security.validation.stop_on_first_failure', false);
    }

    public static function make(mixed $value, string $label): self
    {
        return new self($value, $label);
    }

    public static function file(array $fileData, string $label = 'File'): self
    {
        // For file uploads, $fileData is an entry from $_FILES
        // e.g., $_FILES['myFile']
        $instance = new self($fileData['tmp_name'] ?? null, $label); // Value is initially tmp_name for some checks
        $instance->isFileType = true;
        $instance->fileData = $fileData;
        return $instance;
    }

    public function required(): self
    {
        $this->rules[] = ['type' => 'required'];
        return $this;
    }

    public function email(): self
    {
        $this->rules[] = ['type' => 'email'];
        return $this;
    }

    public function minLength(int $min): self
    {
        $this->rules[] = ['type' => 'minLength', 'params' => ['min' => $min]];
        return $this;
    }

    public function maxLength(int $max): self
    {
        $this->rules[] = ['type' => 'maxLength', 'params' => ['max' => $max]];
        return $this;
    }

    public function numeric(): self
    {
        $this->rules[] = ['type' => 'numeric'];
        return $this;
    }

    public function in(array $allowedValues): self
    {
        $this->rules[] = ['type' => 'in', 'params' => ['values' => $allowedValues]];
        return $this;
    }

    public function maxSize(string $size): self
    {
        if (!$this->isFileType) {
            // This rule is primarily for files, can adapt for string length if needed
            // For now, let's assume it's for files.
            // throw new \LogicException('maxSize rule is for file types only unless adapted.');
        }
        $this->rules[] = ['type' => 'maxSize', 'params' => ['size' => $size]];
        return $this;
    }

    public function allowedTypes(array $types): self
    {
        if (!$this->isFileType) {
            // This rule is for files.
            // throw new \LogicException('allowedTypes rule is for file types only.');
        }
        $this->rules[] = ['type' => 'allowedTypes', 'params' => ['types' => $types]];
        return $this;
    }

    public function validate(): bool
    {
        $this->errors = []; // Reset errors

        foreach ($this->rules as $rule) {
            $methodName = 'validate' . ucfirst($rule['type']);
            if (method_exists($this, $methodName)) {
                $params = $rule['params'] ?? [];
                if (!$this->$methodName($params)) {
                    if ($this->stopOnFirstFailure) {
                        return false;
                    }
                }
            } else {
                // Log or throw exception for unknown validation rule
                error_log("Unknown validation rule type: " . $rule['type']);
            }
        }
        return empty($this->errors);
    }

    public function passes(): bool
    {
        return $this->validate();
    }

    public function fails(): bool
    {
        return !$this->validate();
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    protected function addError(string $ruleKey, array $params = []): void
    {
        $message = $this->errorMessages[$ruleKey] ?? 'Invalid data for :label.';
        $params['label'] = $this->label;
        // Add current value to params if not sensitive
        // $params['value'] = is_scalar($this->value) ? (string) $this->value : gettype($this->value);

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $message = str_replace(':' . $key, (string)$value, $message);
        }
        $this->errors[] = $message;
    }

    // Validation methods
    protected function validateRequired(array $params = []): bool
    {
        if ($this->isFileType) {
            if (!isset($this->fileData['error']) || $this->fileData['error'] === UPLOAD_ERR_NO_FILE) {
                $this->addError('file.required');
                return false;
            }
            // Check for other upload errors as part of 'required' or a separate 'validFile' rule.
            // For now, UPLOAD_ERR_NO_FILE is the primary 'required' check.
            // Other errors indicate an attempt was made, but failed.
            if ($this->fileData['error'] !== UPLOAD_ERR_OK && $this->fileData['error'] !== UPLOAD_ERR_NO_FILE) {
                 $this->addError('file.uploadError', ['errorCode' => $this->fileData['error']]);
                 return false;
            }
             // Ensure tmp_name is present and is an uploaded file if UPLOAD_ERR_OK
            if ($this->fileData['error'] === UPLOAD_ERR_OK && (empty($this->fileData['tmp_name']) || !is_uploaded_file($this->fileData['tmp_name']))) {
                $this->addError('file.validFile'); // Not a valid uploaded file
                return false;
            }

        } elseif (is_string($this->value) && trim($this->value) === '') {
            $this->addError('required');
            return false;
        } elseif ($this->value === null) {
            $this->addError('required');
            return false;
        } elseif (is_array($this->value) && empty($this->value)) {
            $this->addError('required');
            return false;
        }
        return true;
    }

    protected function validateEmail(array $params = []): bool
    {
        if ($this->value === null || $this->value === '') return true; // Not required, so empty is fine
        if (!filter_var($this->value, FILTER_VALIDATE_EMAIL)) {
            $this->addError('email');
            return false;
        }
        return true;
    }

    protected function validateMinLength(array $params): bool
    {
        if ($this->value === null || $this->value === '') return true;
        if (mb_strlen((string)$this->value) < $params['min']) {
            $this->addError('minLength', $params);
            return false;
        }
        return true;
    }

    protected function validateMaxLength(array $params): bool
    {
        if ($this->value === null || $this->value === '') return true;
        if (mb_strlen((string)$this->value) > $params['max']) {
            $this->addError('maxLength', $params);
            return false;
        }
        return true;
    }

    protected function validateNumeric(array $params = []): bool
    {
        if ($this->value === null || $this->value === '') return true;
        if (!is_numeric($this->value)) {
            $this->addError('numeric');
            return false;
        }
        return true;
    }

    protected function validateIn(array $params): bool
    {
        if ($this->value === null || $this->value === '') return true;
        if (!in_array($this->value, $params['values'], true)) { // Strict comparison
            $this->addError('in', ['allowedValues' => implode(', ', $params['values'])]);
            return false;
        }
        return true;
    }

    // File-specific validation methods
    protected function validateMaxSize(array $params): bool
    {
        if (!$this->isFileType || $this->fileData['error'] !== UPLOAD_ERR_OK || empty($this->fileData['tmp_name'])) {
            return true; // Not a file or upload error already handled
        }

        $maxBytes = $this->parseSizeToBytes($params['size']);
        if ($this->fileData['size'] > $maxBytes) {
            $this->addError('file.maxSize', ['maxSize' => $params['size']]);
            return false;
        }
        return true;
    }

    protected function validateAllowedTypes(array $params): bool
    {
        if (!$this->isFileType || $this->fileData['error'] !== UPLOAD_ERR_OK || empty($this->fileData['tmp_name'])) {
            return true; // Not a file or upload error already handled
        }

        $allowedMimeTypes = $params['types'];
        $actualMimeType = null;

        if (function_exists('mime_content_type') && file_exists($this->fileData['tmp_name'])) {
            $actualMimeType = mime_content_type($this->fileData['tmp_name']);
        } elseif (function_exists('finfo_open') && file_exists($this->fileData['tmp_name'])) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $actualMimeType = finfo_file($finfo, $this->fileData['tmp_name']);
            finfo_close($finfo);
        } else {
            // Fallback to client-provided type if server functions are unavailable (less secure)
            $actualMimeType = $this->fileData['type'] ?? null;
             error_log("Validator: mime_content_type and finfo are not available. Falling back to client-provided MIME type for " . $this->fileData['name']);
        }


        if ($actualMimeType === null || !in_array(strtolower($actualMimeType), array_map('strtolower', $allowedMimeTypes))) {
             // Try to load default from config if $params['types'] was empty (though the rule implies it's provided)
            if (empty($allowedMimeTypes)) {
                $app = Application::getInstance();
                $allowedMimeTypes = $app->getConfig('security.upload_security.allowed_mime_types', []);
                if (empty($allowedMimeTypes)) {
                     $this->addError('file.allowedTypes', ['types' => 'N/A (config missing)']);
                     return false; // No types configured to check against
                }
                 if (!in_array(strtolower($actualMimeType), array_map('strtolower', $allowedMimeTypes))) {
                    $this->addError('file.allowedTypes', ['types' => implode(', ', $allowedMimeTypes)]);
                    return false;
                 }
            } else {
                 $this->addError('file.allowedTypes', ['types' => implode(', ', $allowedMimeTypes)]);
                 return false;
            }
        }
        return true;
    }

    protected function parseSizeToBytes(string $sizeStr): int
    {
        $sizeStr = strtoupper(trim($sizeStr));
        $value = (int)rtrim($sizeStr, 'KMGTPEZYB');
        $unit = substr($sizeStr, -1);
        if(!is_numeric($unit)) { // Check if last char is one of KMGT...
             $unit = substr($sizeStr, -2,1); // For KB, MB etc.
        } else {
            $unit = ''; // No unit, assume bytes
        }


        switch ($unit) {
            case 'K': $value *= 1024; break;
            case 'M': $value *= 1024 * 1024; break;
            case 'G': $value *= 1024 * 1024 * 1024; break;
            case 'T': $value *= 1024 * 1024 * 1024 * 1024; break;
            // Add P, E, Z, Y if needed
        }
        return $value;
    }
}