<?php
function getHeadlines() {
    $url = 'https://mashable.com/';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode != 200 || empty($html)) {
        echo "Failed to retrieve content. HTTP Status Code: $httpCode";
        return [];
    }

    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    $articles = $xpath->query('//div[@class="broll_info"]/div[@class="caption"]/a');
    $dates = $xpath->query('//div[@class="broll_info"]/time[@class="datepublished"]');

    $headlines = [];
    foreach ($articles as $index => $article) {
        $headlineText = trim($article->nodeValue);
        $link = $article->getAttribute('href');
        $fullLink = strpos($link, 'http') === 0 ? $link : 'https://mashable.com' . $link;

        if (isset($dates[$index])) {
            $dateString = $dates[$index]->nodeValue;
            $pubDate = DateTime::createFromFormat('M. d, Y', $dateString);

            if ($pubDate) {
                $headlines[] = [
                    'title' => $headlineText,
                    'link' => $fullLink,
                    'date' => $pubDate
                ];
            }
        }
    }

    return $headlines;
}

function filterHeadlines($headlines) {
    $filteredHeadlines = [];
    $cutoffDate = new DateTime('2022-01-01');

    foreach ($headlines as $headline) {
        if ($headline['date'] >= $cutoffDate) {
            $filteredHeadlines[] = $headline;
        }
    }

    return $filteredHeadlines;
}

$headlines = getHeadlines();
$filteredHeadlines = filterHeadlines($headlines);

usort($filteredHeadlines, function($a, $b) {
    return $b['date'] <=> $a['date'];
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mashable Headline Aggregator</title>
</head>
<body>

<h1>Mashable Article Headlines (From 2022 to Now)</h1>

<ul>
    <?php if (!empty($filteredHeadlines)): ?>
        <?php foreach ($filteredHeadlines as $headline): ?>
            <li>
                <a href="<?php echo htmlspecialchars($headline['link']); ?>" target="_blank">
                    <?php echo htmlspecialchars($headline['title']); ?>
                </a>
                <br>
                <small>Published on: <?php echo $headline['date']->format('d F, Y'); ?></small>
            </li>
        <?php endforeach; ?>
    <?php else: ?>
        <li>No articles found from January 1st, 2022 onwards.</li>
    <?php endif; ?>
</ul>

</body>
</html>
