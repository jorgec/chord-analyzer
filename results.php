<?php
// results.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analysis Results</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .fretboard {
            display: flex;
            flex-direction: column;
        }
        .string {
            display: flex;
        }
        .fret {
            width: 40px;
            height: 25px;
            border-right: 1px solid #ccc;
            position: relative;
        }
        .note {
            position: absolute;
            width: 100%;
            height: 100%;
            text-align: center;
            line-height: 25px;
        }
        .fret-number {
            width: 40px;
            height: 25px;
            text-align: center;
            line-height: 25px;
            font-weight: bold;
        }
        .note.root {
            color: red;
        }
        .note.third {
            color: blue;
        }
        .note.seventh {
            color: green;
        }
        .note.second {
            color: orange;
        }
        .note.extension {
            color: pink;
        }
    </style>
</head>
<body class="bg-gray-100">
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-4">Analysis Results</h1>
    <h2 class="text-2xl font-semibold mb-2">Key of the Progression: <?php echo $analysis['key']; ?></h2>
    <p class="mb-4">Confidence Level: <?php echo round($analysis['confidence'], 2); ?>%</p>
    <h3 class="text-xl font-semibold mb-2">Key Detection Explanation:</h3>
    <ul class="list-disc list-inside mb-6">
        <?php foreach ($analysis['key_explanation'] as $explanation): ?>
            <li><?php echo $explanation; ?></li>
        <?php endforeach; ?>
    </ul>

    <h2 class="text-2xl font-semibold">Chord Analysis</h2>
    <table class="table-auto w-full mb-6">
        <thead>
        <tr>
            <th class="px-4 py-2">Chord</th>
            <th class="px-4 py-2">Function</th>
            <th class="px-4 py-2">Intervals</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($analysis['chords'] as $index => $chord): ?>
            <tr class="bg-white">
                <td class="border px-4 py-2"><?php echo htmlspecialchars($chord); ?></td>
                <td class="border px-4 py-2"><?php echo $analysis['functions'][$index]; ?></td>
                <td class="border px-4 py-2"><?php echo implode(', ', $analysis['intervals'][$index]); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="text-2xl font-semibold mb-4">Suggested Scale on Guitar Fretboard (0-15th fret)</h2>
    <p class="mb-2">Note Colors: <span class="text-red-500">Root (1st)</span>, <span class="text-blue-500">3rd</span>, <span class="text-green-500">7th</span>, <span class="text-orange-500">2nd</span>, <span class="text-pink-500">Chord Extensions</span></p>
    <div class="fretboard bg-white p-4 rounded shadow">
        <?php
        $strings = ['E', 'B', 'G', 'D', 'A', 'E'];
        $frets = range(0, 15);
        $key_root = normalize_note_name(substr($analysis['key'], 0, -1));
        $key_type = substr($analysis['key'], -1);
        $scale_degrees = get_scale_degrees($key_root, $key_type);
        $interval_notes = [
            'root' => $key_root,
            'second' => array_keys($scale_degrees)[1],
            'third' => array_keys($scale_degrees)[2],
            'seventh' => array_keys($scale_degrees)[6],
        ];

        foreach ($strings as $string) {
            echo '<div class="string">';
            foreach ($frets as $fret) {
                echo '<div class="fret">';
                // Display notes in the suggested scale
                $note = get_note_at_fret($string, $fret);
                $note_normalized = normalize_note_name($note);
                if (in_array($note_normalized, $analysis['suggested_scale'])) {
                    $note_class = '';
                    if ($note_normalized == $interval_notes['root']) {
                        $note_class = 'root';
                    } elseif ($note_normalized == $interval_notes['second']) {
                        $note_class = 'second';
                    } elseif ($note_normalized == $interval_notes['third']) {
                        $note_class = 'third';
                    } elseif ($note_normalized == $interval_notes['seventh']) {
                        $note_class = 'seventh';
                    }

                    // Check if the note is part of a chord extension
                    $is_extension = false;
                    foreach ($analysis['intervals'] as $chord_intervals) {
                        if (in_array('9', $chord_intervals) && $note_normalized == $interval_notes['second']) {
                            $is_extension = true;
                            break;
                        }
                        if (in_array('11', $chord_intervals) && $note_normalized == array_keys($scale_degrees)[3]) {
                            $is_extension = true;
                            break;
                        }
                        if (in_array('13', $chord_intervals) && $note_normalized == array_keys($scale_degrees)[5]) {
                            $is_extension = true;
                            break;
                        }
                    }
                    if ($is_extension) {
                        $note_class = 'extension';
                    }

                    echo '<div class="note ' . $note_class . '">' . $note . '</div>';
                }
                echo '</div>';
            }
            echo '</div>';
        }

        // Fret numbers at the bottom
        echo '<div class="string">';
        foreach ($frets as $fret) {
            echo '<div class="fret-number">' . $fret . '</div>';
        }
        echo '</div>';

        function get_note_at_fret($open_string, $fret) {
            $notes_sharp = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];
            $notes_flat  = ['C', 'Db', 'D', 'Eb', 'E', 'F', 'Gb', 'G', 'Ab', 'A', 'Bb', 'B'];

            $string_note = $open_string;
            $start_index = array_search($string_note, $notes_sharp);
            if ($start_index === false) {
                $start_index = array_search($string_note, $notes_flat);
            }
            $note_index = ($start_index + $fret) % 12;
            return $notes_sharp[$note_index];
        }
        ?>
    </div>
</div>
</body>
</html>
