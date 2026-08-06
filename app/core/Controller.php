<?php

abstract class Controller
{

    protected function view(string $path, array $data = []): void
    {
        extract($data);

        require $path;
    }

    protected function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }

}