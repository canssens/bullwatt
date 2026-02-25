<?php
header("Content-Type: text/plain");
// Path to the template and folders
$templatePath = __DIR__ . '/template.html';
$howToDir = __DIR__ ;


// Example data for a guide (adapt as needed)
$howtoData = [
    [
        'title' => 'Bullwatt vs. Zwift: Why Choose an Open-Source Cycling Simulator?',
        'content' => '<p>Focus on: No subscription, privacy, and community-driven features</p>',
        'slug' => 'guide1.html'
    ],
    [
        'title' => 'The Ultimate Guide: How to Train Indoors on Linux/Web Without a Subscription.',
        'content' => '<h1>Guide 2</h1><p>Contenu spécifique pour le guide 2...</p>',
        'slug' => 'guide2.html'
    ],
    [
        'title' => 'Compatible Hardware: A Comprehensive List of Web Bluetooth & FTMS Sensors for Indoor Cycling.',
        'content' => '<h1>Guide 2</h1><p>Contenu spécifique pour le guide 2...</p>',
        'slug' => 'guide3.html'
    ]
    // Add more howto here

];

// Generate each how-to page
foreach ($howtoData as $guide) {
    $template = file_get_contents($templatePath);
    $pageContent = str_replace('{TITLE}', $guide['title'], $template);
    $pageContent = str_replace('{CONTENT}', $guide['content'], $pageContent);
    file_put_contents($howToDir . '/' . $guide['slug'], $pageContent);
}

// Generate the list of howto for the index
$howtoList = '';
foreach ($howtoData as $guide) {
    $howtoList .= '<div class="col-md-4"><a href="how-to/'.$guide["slug"].'">'."\n";
    $howtoList .= '    <div class="card h-100 border-0 text-center p-4"><h3>'.$guide["title"].'</h3> </div>'."\n";
    $howtoList .= "</a></div>\n\n";



}



echo "Pages and index generated successfully.\n";
echo "==================================\n";

echo $howtoList;
?>