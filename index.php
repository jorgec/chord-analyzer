<?php
// index.php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $chords_input = $_POST['chords'];
    $scale_type = $_POST['scale_type'];
    $tempo = $_POST['tempo'];

    // Process chords
    $chords = explode(',', str_replace(' ', '', $chords_input));

    // Analyze chords
    include 'music_functions.php';

    $analysis = analyze_chords($chords, $scale_type);

    // Render results
    include 'results.php';
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chord Progression Analyzer (beta)</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6">
        Chord Progression Analyzer
        <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">BETA</span>
    </h1>
    <form method="POST" class="space-y-4">
        <div>
            <label class="block text-xl font-semibold">Chord Progression (comma-separated):</label>
            <input type="text" name="chords" class="w-full p-2 border border-gray-300 rounded" placeholder="Em, C#m7, G#m7b5" required>
        </div>
        <input type="hidden" name="scale_type" value="diatonic" />
        <input type="hidden" name="tempo" value="slow" />
<!--        <div>-->
<!--            <label class="block text-xl font-semibold">Scale Type:</label>-->
<!--            <select name="scale_type" class="w-full p-2 border border-gray-300 rounded">-->
<!--                <option value="diatonic">Diatonic</option>-->
<!--                <option value="chromatic">Chromatic</option>-->
<!--            </select>-->
<!--        </div>-->
<!--        <div>-->
<!--            <label class="block text-xl font-semibold">Tempo:</label>-->
<!--            <select name="tempo" class="w-full p-2 border border-gray-300 rounded">-->
<!--                <option value="slow">Slow (&lt; 90 BPM)</option>-->
<!--                <option value="medium">Medium (&lt; 140 BPM)</option>-->
<!--                <option value="fast">Fast (&ge; 140 BPM)</option>-->
<!--            </select>-->
<!--        </div>-->
        <button type="submit" class="bg-blue-500 text-white p-2 rounded">Analyze</button>
    </form>
</div>
</body>
</html>
