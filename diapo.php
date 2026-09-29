<?php

class Media
{
    public const MEDIA_LIST_LINE_REGEX = '#^(?:.*/)?([^/]+?)\.([^./]+): ([^/:]+)/([^/:]+)$#';

    private string $baseMimeType;
    private string $subMimeType;
    private string $name;
    private string $extension;

    public function __construct(string $baseMimeType, string $subMimeType, string $name, string $extension)
    {
        $this->baseMimeType = $baseMimeType;
        $this->subMimeType = $subMimeType;
        $this->name = $name;
        $this->extension = $extension;
    }

    /**
     * Returns null if the line does not match, or if a capture group is unmatched.
     */
    public static function createFromMediaLine(string $line): ?self
    {
        if (preg_match(self::MEDIA_LIST_LINE_REGEX, $line, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
            return null;
        }

        if (in_array(null, $matches, true)) {
            return null;
        }

        [, $name, $extension, $baseMimeType, $subMimeType] = $matches;

        return new self($baseMimeType, $subMimeType, $name, $extension);
    }

    public function getBaseMimeType(): string
    {
        return $this->baseMimeType;
    }

    public function getSubMimeType(): string
    {
        return $this->subMimeType;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function getMimeType(): string
    {
        return sprintf('%s/%s', $this->baseMimeType, $this->subMimeType);
    }

    public function getPosterPath(): string
    {
        return sprintf('media/poster/%s.webp', $this->name);
    }

    public function getThumbnailPath(): string
    {
        return sprintf('media/thumbnail/%s.webp', $this->name);
    }

    public function getMediaPath(): string
    {
        return sprintf('media/%s.%s', $this->name, $this->extension);
    }
}

function readMediaList(string $path, array &$medias): void
{
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        fwrite(STDERR, sprintf("could not read media list: %s\n", $path));
        return;
    }

    foreach ($lines as $number => $line) {
        if ($line === '') {
            continue;
        }
        $media = Media::createFromMediaLine($line);
        if ($media === null) {
            fwrite(STDERR, sprintf("%s:%d: skipping invalid line: %s\n", $path, $number + 1, $line));
            continue;
        }
        $medias[] = $media;
    }
}



$currentDir = basename(__DIR__);
$medias = [];
readMediaList('media_list', $medias);

usort($medias, function($a, $b) {
    return strcmp(basename($a->getMediaPath()), basename($b->getMediaPath()));
});

$total = count($medias);
$indexMax = $total - 1;

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $currentDir ?></title>
    <link rel="stylesheet" href="diapo.css">
    <meta name="generator" content="https://github.com/vincent-peugnet/diapo">
</head>
<body>

<nav>
    <h1>
        <?= $currentDir ?>
    </h1>

    <p>
        <?= $total ?> files
    </p>

    <ul>
    <?php foreach ($files as $file) {
        ?>
            <li>
                <a href="#<?= $file ?>">
                    <img src="media/thumbnail/<?= pathinfo($file, PATHINFO_FILENAME) ?>.webp" alt="" loading="lazy">
                </a>
            </li>
        <?php
    } ?>
    </ul>
</nav>

<main dir="ltr">
    <?php foreach ($files as $key => $file) {
        if ($key == 0) {
            $prev = $files[$indexMax];
        } else {
            $prev = $files[$key - 1];
        }
        if ($key == $indexMax) {
            $next = $files[0];
        } else {
            $next = $files[$key + 1];
        }
        echo "<div id=\"$file\">";
        echo "<a class=\"dir prev\" href=\"#$prev\"></a>";
        switch (pathinfo($file, PATHINFO_EXTENSION)) {
            case 'webm':
                ?>
                <video controls preload="none" poster="media/poster/<?= pathinfo($file, PATHINFO_FILENAME) ?>.webp" class="media">
                    <source src="media/<?= $file ?>" type="video/webm">
                </video>
                <?php
                break;
            
            default:
                ?>
                <img src="media/<?= $file ?>" alt="" loading="lazy" class="media">
                <?php
                break;
        }
        echo "<a class=\"dir next\" href=\"#$next\"></a>";
        echo '</div>';
    } ?>
</main>
    
</body>
</html>
