<?php

namespace App\Services;

use Parsedown;

class MarkdownService
{
    protected $parsedown;
    
    public function __construct()
    {
        $this->parsedown = new Parsedown();
        $this->parsedown->setSafeMode(true); // Enable safe mode to prevent XSS
        $this->parsedown->setBreaksEnabled(true); // Convert line breaks to <br>
    }
    
    /**
     * Parse markdown to HTML
     */
    public function parse(string $markdown): string
    {
        return $this->parsedown->text($markdown);
    }
    
    /**
     * Parse markdown to HTML with custom CSS classes
     */
    public function parseWithStyles(string $markdown): string
    {
        $html = $this->parse($markdown);
        
        // Add Tailwind CSS classes to generated HTML elements
        $replacements = [
            '<h1>' => '<h1 class="text-2xl font-bold mb-4 text-gray-900">',
            '<h2>' => '<h2 class="text-xl font-semibold mb-3 text-gray-800">',
            '<h3>' => '<h3 class="text-lg font-semibold mb-2 text-gray-800">',
            '<h4>' => '<h4 class="text-base font-semibold mb-2 text-gray-700">',
            '<p>' => '<p class="mb-4 text-gray-700">',
            '<ul>' => '<ul class="list-disc list-inside mb-4 text-gray-700">',
            '<ol>' => '<ol class="list-decimal list-inside mb-4 text-gray-700">',
            '<li>' => '<li class="mb-1">',
            '<blockquote>' => '<blockquote class="border-l-4 border-blue-500 pl-4 my-4 italic text-gray-600">',
            '<code>' => '<code class="bg-gray-100 px-1 py-0.5 rounded text-sm font-mono">',
            '<pre>' => '<pre class="bg-gray-100 p-4 rounded-lg overflow-x-auto mb-4">',
            '<hr>' => '<hr class="my-6 border-gray-300">',
            '<table>' => '<table class="min-w-full divide-y divide-gray-200 mb-4">',
            '<thead>' => '<thead class="bg-gray-50">',
            '<tbody>' => '<tbody class="bg-white divide-y divide-gray-200">',
            '<th>' => '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">',
            '<td>' => '<td class="px-3 py-2 text-sm text-gray-900">',
            '<strong>' => '<strong class="font-semibold">',
            '<em>' => '<em class="italic">',
            '<a href=' => '<a class="text-blue-600 hover:text-blue-800 underline" href=',
        ];
        
        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $html
        );
    }
    
    /**
     * Strip markdown and return plain text
     */
    public function stripMarkdown(string $markdown): string
    {
        // Parse to HTML first
        $html = $this->parse($markdown);
        
        // Strip HTML tags
        return strip_tags($html);
    }
    
    /**
     * Get a preview of markdown content
     */
    public function preview(string $markdown, int $length = 200): string
    {
        $plainText = $this->stripMarkdown($markdown);
        
        if (strlen($plainText) <= $length) {
            return $plainText;
        }
        
        return substr($plainText, 0, $length) . '...';
    }
}