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
        <?php foreach ($medias as $media): ?>
            <li>
                <a href="#<?= basename($media->getMediaPath()) ?>">
                    <img src="<?= $media->getThumbnailPath() ?>" alt="" loading="lazy">
                </a>
            </li>
        <?php endforeach ?>
    </ul>
</nav>

<main dir="ltr">
    <?php foreach ($medias as $key => $media): ?>
        <?php
        $filename = basename($media->getMediaPath());
        $prev = basename($medias[$key == 0 ? $indexMax : $key - 1]->getMediaPath());
        $next = basename($medias[$key == $indexMax ? 0 : $key + 1]->getMediaPath());
        ?>
        <div id="<?= $filename ?>">
            <a class="dir prev" href="#<?= $prev ?>"></a>
            <?php if ($media->getBaseMimeType() === 'video'): ?>
                <video controls preload="none" poster="<?= $media->getPosterPath() ?>" class="media">
                    <source src="<?= $media->getMediaPath() ?>" type="<?= $media->getMimeType() ?>">
                </video>
            <?php else: ?>
                <img src="<?= $media->getMediaPath() ?>" alt="" loading="lazy" class="media">
            <?php endif ?>
            <a class="dir next" href="#<?= $next ?>"></a>
        </div>
    <?php endforeach ?>
</main>
    
</body>
</html>
