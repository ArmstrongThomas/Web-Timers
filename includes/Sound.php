<?php

class Sound
{
    private $availableSounds = [];
    private $soundsDirectory;
    
    public function __construct($soundsDirectory)
    {
        // Validate soundsDirectory is a valid path
        if (!is_string($soundsDirectory) || !is_dir($soundsDirectory)) {
            throw new InvalidArgumentException("Invalid sounds directory specified");
        }
        
        // Prevent directory traversal by resolving path
        $this->soundsDirectory = realpath($soundsDirectory);
        $this->loadAvailableSounds();
    }
    
    /**
     * Loads all available sounds from the sounds directory
     */
    private function loadAvailableSounds(): void
    {
        $this->availableSounds = [];
        $allowed_extensions = ['mp3', 'wav', 'ogg'];
        
        if (is_dir($this->soundsDirectory)) {
            $files = scandir($this->soundsDirectory);
            
            if ($files === false) {
                // Log error accessing directory
                error_log("Error accessing sounds directory: " . $this->soundsDirectory);
                return;
            }
            
            foreach ($files as $file) {
                // Skip dot files and validate filename
                if ($file === '.' || $file === '..' || !preg_match('/^[a-zA-Z0-9_\-\.]+\.(mp3|wav|ogg)$/i', $file)) {
                    continue;
                }
                
                $file_path = $this->soundsDirectory . '/' . $file;
                $file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                
                if (in_array($file_extension, $allowed_extensions, true) && is_file($file_path)) {
                    $this->availableSounds[] = [
                        'path' => '/sounds/' . $file,
                        'name' => pathinfo($file, PATHINFO_FILENAME),
                        'type' => $file_extension
                    ];
                }
            }
        }
    }
    
    /**
     * Returns an array of all available sounds
     * 
     * @return array Array of sound information with path, name and type
     */
    public function getAvailableSounds(): array
    {
        return $this->availableSounds;
    }
    
    /**
     * Validates if a sound exists
     * 
     * @param string $soundPath The path to check
     * @return bool True if the sound exists, false otherwise
     */
    public function validateSound(string $soundPath): bool
    {
        foreach ($this->availableSounds as $sound) {
            if ($sound['path'] === $soundPath) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Gets metadata for a specific sound
     * 
     * @param string $soundPath The path of the sound
     * @return array|null Sound metadata or null if not found
     */
    public function getSoundMetadata(string $soundPath): ?array
    {
        foreach ($this->availableSounds as $sound) {
            if ($sound['path'] === $soundPath) {
                return $sound;
            }
        }
        return null;
    }
}
?>