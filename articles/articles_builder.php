<?php
header("Content-Type: text/plain");
// Path to the template and folders
$templatePath = __DIR__ . '/template.html';
$articleDir = __DIR__;


// Example data for a guide (adapt as needed)
$articleData = [
    [
        'title' => 'General Information about BullWatt: A Free, Web-Based Indoor Cycling Training Tool',
        'content' => './html_blocks/production-information.html',
        'slug' => 'production-information.html'
    ],
    [
        'title' => 'Bullwatt vs. Zwift: Why Choose an Open-Source Cycling Training Application?',
        'content' => './html_blocks/bullwatt-vs-zwift.html',
        'slug' => 'bullwatt-vs-zwift.html'
    ],
    [
        'title' => 'The Quick Guide: How to Train Indoors on Web Without a Subscription',
        'content' => './html_blocks/bullwatt-no-subscription.html',
        'slug' => 'bullwatt-no-subscription.html'
    ],
    [
        'title' => 'Compatible Hardware',
        'content' => './html_blocks/compatible-hardware.html',
        'slug' => 'compatible-hardware.html'
    ],
    [
        'title' => 'Why BullWatt is a good Zwift Alternative for Older PCs',
        'content' => './html_blocks/hardware-zwift-slow.html',
        'slug' => 'hardware-zwift-slow.html'
    ]
    // Add more howto here

];

// Generate each how-to page
foreach ($articleData as $guide) {
    $template = file_get_contents($templatePath);
    $content = file_get_contents($guide['content']);
    $pageContent = str_replace('{SLUG}', $guide['slug'], $template);
    $pageContent = str_replace('{TITLE}', $guide['title'], $pageContent);
    $pageContent = str_replace('{CONTENT}', $content, $pageContent);
    file_put_contents($articleDir . '/' . $guide['slug'], $pageContent);
}

// Generate the list of howto for the index
$articleList = '';
foreach ($articleData as $guide) {
    $articleList .= '<div class="col-md-4"><a href="articles/' . $guide["slug"] . '">' . "\n";
    $articleList .= '    <div class="card h-100 border-0 text-center p-4"><h3 data-i18n="articles.' . $guide["slug"] . '">' . $guide["title"] . '</h3> </div>' . "\n";
    $articleList .= "</a>\n</div>\n\n";
}



echo "Pages and index generated successfully.\n";
echo "==================================\n";

echo $articleList;
?>