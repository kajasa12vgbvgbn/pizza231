<?php
namespace App;

class BaseTemplate {
    public function render($content) {
        echo "<html><head><title>Магазин</title></head><body>";
        echo $content;
        echo "</body></html>";
    }
}