<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class TinyEditor extends Field
{
    protected string $view = 'forms.components.tiny-editor';

    protected int|string $height = 400;
    protected bool $menubar = false;
    protected string $toolbar = 'undo redo | styles | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | blockquote hr | code removeformat | fullscreen';
    protected string $plugins = 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount';

    public function height(int|string $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function menubar(bool $menubar = true): static
    {
        $this->menubar = $menubar;

        return $this;
    }

    public function toolbar(string $toolbar): static
    {
        $this->toolbar = $toolbar;

        return $this;
    }

    public function plugins(string $plugins): static
    {
        $this->plugins = $plugins;

        return $this;
    }

    public function getHeight(): int|string
    {
        return $this->height;
    }

    public function getMenubar(): bool
    {
        return $this->menubar;
    }

    public function getToolbar(): string
    {
        return $this->toolbar;
    }

    public function getPlugins(): string
    {
        return $this->plugins;
    }

    public function getApiKey(): string
    {
        return config('services.tinymce.api_key', 'no-api-key');
    }
}
