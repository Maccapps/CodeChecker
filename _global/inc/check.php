<?php
$project = $codeCheck->cfg['projects'][$_SESSION['current']['project']];
$_SESSION['current']['folder'] = $_SESSION['current']['folder'] === null ? $project['base'] : $_SESSION['current']['folder'];
$folder = $_SESSION['current']['folder'];
$file = $_SESSION['current']['folder'] . '\\' . $_SESSION['current']['file'];
$parent = $_SESSION['current']['parent'];
$filetype = pathinfo($file, PATHINFO_EXTENSION);

if ($filetype === 'js') {
    if (!array_key_exists('jslintdone', $_SESSION) OR $_SESSION['jslintdone'] !== true) {
        $issues['items']['errors'] = $codeCheck->sniffer->getFormattedResults($file, $project['type'], $filetype);
        die();
    } else {
        $issues['items']['errors'] = $codeCheck->formatJsLintResults($_SESSION['jslint'], $file);
        $_SESSION['jslintdone'] = false;
    }
    $projectReportDir = $codeCheck->reportsJsonFile . str_replace('.json', '', $project['file']);
} else {
    $issues['items'] = $codeCheck->sniffer->getFormattedResults($file, $project['type'], $filetype);
    $issues['items']['security'] = getSecurityReport($file, $project['type']);
    $projectReportDir = '../'.$codeCheck->reportsJsonFile . str_replace('.json', '', $project['file']);
}

$issues['counts']['errors'] = count($issues['items']['errors']);
$issues['counts']['warnings'] = count($issues['items']['warnings']);
$issues['counts']['security'] = count($issues['items']['security']);

if (!is_dir($projectReportDir)) {
    mkdir($projectReportDir);
}
$hashfilename = str_replace($project['base'].'\\', '', $_SESSION['current']['folder'].'\\'.$_SESSION['current']['file']);
$reportFile = $projectReportDir . '/' . md5($hashfilename) . '.json';
$fp = fopen($reportFile, 'w');

$dd['hash'] = $contentsHash = md5(file_get_contents($file));
$dd['errors'] = $issues['counts']['errors'];
$dd['warnings'] = $issues['counts']['warnings'];
$dd['security'] = $issues['counts']['security'];
fwrite($fp, json_encode($dd, true));
fclose($fp);

?>
    <title><?php echo $_SESSION['current']['file']; ?> | <?php echo $project['name']; ?> | CodeChecker</title>
</head>
<body>

<header class="main-header">
    <nav class="constant-nav clearfix">
        <ul>
            <li class="nav-cc"><a href="?key=action&val=projects">CodeChecker</a></li> 
            <li class="nav-visit-site"><a href="?key=folder&val=<?php echo $project['base']; ?>"><?php echo $project['name']; ?></a></li>
            <li class="nav-edit"><a href="?edit=explorer+<?php echo urlencode($file); ?>"><?php echo $_SESSION['current']['file']; ?></a></li>
        </ul>
    </nav>
</header>

<div class="main-content cf">
    <h1><?php echo $project['name']; ?> <span>(<a href="<?php echo '?edit=explorer+'.urlencode($codeCheck->cfg['standards'][$project['type']]['file']).''; ?>"><?php echo $codeCheck->cfg['standards'][$project['type']]['name']; ?></a>)</span></h1>

<div id="table-holder-explorer" class="table-holder">
<?php 
    $codeCheck->outputCurrentPath($folder, $project);
    $contents = $codeCheck->fileServer->getFolderContents($folder);
    $codeCheck->outputFolderContents($folder, $project, $contents);
?>
</div>

<div id="table-holder-results" class="table-holder">
<?php
    $codeCheck->outputFileIssueHeader($folder, $project, $issues['items'], $file);
    $codeCheck->outputFileIssues($folder, $project, $issues['items']);
?>
</div>

<div class="report"><pre></pre></div>

</div>