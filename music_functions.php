<?php
// music_functions.php

function analyze_chords($chords, $scale_type) {
    // Determine the key of the progression with confidence levels and explanations
    list($key, $confidence, $key_explanation) = determine_key($chords);

    $functions = [];
    $intervals = [];
    $suggested_scale = get_suggested_scale($key, $scale_type);

    foreach ($chords as $chord) {
        $root_note = get_root_note_from_chord($chord);
        $intervals[] = get_intervals_from_chord($chord);
        $functions[] = get_function($root_note, $key);
    }

    return [
        'chords' => $chords,
        'key' => $key,
        'confidence' => $confidence,
        'key_explanation' => $key_explanation,
        'functions' => $functions,
        'intervals' => $intervals,
        'suggested_scale' => $suggested_scale,
    ];
}

function get_root_note_from_chord($chord) {
    // Extract the root note from the chord notation
    preg_match('/^[A-G][#b]?/', $chord, $matches);
    return $matches[0];
}

function get_chord_quality($chord) {
    // Normalize chord notation to identify base chord quality
    $quality = 'major'; // Default to major

    if (preg_match('/^(?:[A-G][#b]?)(.*)$/', $chord, $matches)) {
        $suffix = $matches[1];

        // Remove numeric extensions
        $suffix = preg_replace('/\d+/', '', $suffix);

        // Remove parentheses and other non-essential characters
        $suffix = str_replace(['(', ')'], '', $suffix);

        // Map suffixes to chord qualities
        if (strpos($suffix, 'dim') !== false || strpos($suffix, '°') !== false) {
            $quality = 'diminished';
        } elseif (strpos($suffix, 'm7b5') !== false || strpos($suffix, 'ø') !== false) {
            $quality = 'half-diminished';
        } elseif (strpos($suffix, 'maj') !== false) {
            $quality = 'major';
        } elseif (strpos($suffix, 'm') !== false) {
            $quality = 'minor';
        } elseif (strpos($suffix, 'aug') !== false || strpos($suffix, '+') !== false) {
            $quality = 'augmented';
        } elseif (strpos($suffix, '7') !== false) {
            $quality = 'dominant';
        }
    }

    return $quality;
}

function get_intervals_from_chord($chord) {
    // Determine intervals based on chord notation
    $intervals = [];
    $quality = get_chord_quality($chord);

    switch ($quality) {
        case 'diminished':
            $intervals = ['1', 'b3', 'b5'];
            break;
        case 'half-diminished':
            $intervals = ['1', 'b3', 'b5', 'b7'];
            break;
        case 'minor':
            $intervals = ['1', 'b3', '5'];
            break;
        case 'major':
            $intervals = ['1', '3', '5'];
            break;
        case 'augmented':
            $intervals = ['1', '3', '#5'];
            break;
        case 'dominant':
            $intervals = ['1', '3', '5', 'b7'];
            break;
        default:
            $intervals = ['1', '3', '5'];
            break;
    }

    // Identify extensions
    if (preg_match('/[^\d](\d+)/', $chord, $matches)) {
        $extension = intval($matches[1]);
        if ($extension == 7 && $quality == 'major') {
            $intervals[] = '7';
        } elseif ($extension == 7) {
            $intervals[] = 'b7';
        } elseif ($extension == 9) {
            $intervals[] = '9';
        } elseif ($extension == 11) {
            $intervals[] = '11';
        } elseif ($extension == 13) {
            $intervals[] = '13';
        }
    }

    return $intervals;
}

function determine_key($chords) {
    // Advanced key detection with confidence levels and explanations
    $possible_keys = generate_possible_keys();
    $key_scores = [];

    foreach ($possible_keys as $key => $key_data) {
        $key_chords = $key_data['chords'];
        $score = 0;
        $explanation = [];

        foreach ($chords as $chord) {
            $root_note = get_root_note_from_chord($chord);
            $chord_quality = get_chord_quality($chord);
            $normalized_chord = $root_note . ' ' . $chord_quality;

            // Check if the chord exists in the key and get its degree
            $degree = array_search($normalized_chord, $key_chords);
            if ($degree !== false) {
                // Assign weights based on harmonic function
                switch ($degree) {
                    case 0: // Tonic (I or i)
                        $score += 3;
                        $explanation[] = "$chord functions as Tonic in $key";
                        break;
                    case 4: // Dominant (V or v)
                        $score += 2;
                        $explanation[] = "$chord functions as Dominant in $key";
                        break;
                    case 3: // Subdominant (IV or iv)
                        $score += 2;
                        $explanation[] = "$chord functions as Subdominant in $key";
                        break;
                    default:
                        $score += 1;
                        $explanation[] = "$chord is diatonic in $key";
                        break;
                }
            } else {
                // Check for secondary dominants and borrowed chords
                if (is_secondary_dominant($chord, $key)) {
                    $score += 1;
                    $explanation[] = "$chord is a secondary dominant in $key";
                } elseif (is_borrowed_chord($chord, $key)) {
                    $score += 0.5;
                    $explanation[] = "$chord is a borrowed chord in $key";
                } else {
                    $explanation[] = "$chord is non-diatonic in $key";
                }
            }
        }
        $key_scores[$key] = ['score' => $score, 'explanation' => $explanation];
    }

    // Select the key with the highest score
    uasort($key_scores, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    reset($key_scores);
    $best_key = key($key_scores);
    $best_score = $key_scores[$best_key]['score'];
    $key_explanation = $key_scores[$best_key]['explanation'];

    // Calculate confidence level
    $max_possible_score = count($chords) * 3; // Max weight per chord is 3
    $confidence = ($best_score / $max_possible_score) * 100; // Percentage

    return [$best_key, $confidence, $key_explanation];
}

function is_secondary_dominant($chord, $key) {
    // Placeholder function for detecting secondary dominants
    // In a full implementation, this would check if the chord is a V/V, V/ii, etc.
    // For simplicity, we'll return false here
    return false;
}

function is_borrowed_chord($chord, $key) {
    // Placeholder function for detecting borrowed chords
    // In a full implementation, this would check if the chord is borrowed from parallel modes
    // For simplicity, we'll return false here
    return false;
}

function generate_possible_keys() {
    // Generate a list of possible keys with their diatonic chords
    $keys = [];
    $note_names = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];

    foreach ($note_names as $note) {
        // Major keys
        $major_scale = get_scale($note, 'major');
        $chord_qualities = ['major', 'minor', 'minor', 'major', 'major', 'minor', 'diminished'];
        $key_chords = [];
        for ($i = 0; $i < 7; $i++) {
            $key_chords[] = $major_scale[$i] . ' ' . $chord_qualities[$i];
        }
        $keys[$note . 'M'] = ['scale_type' => 'major', 'chords' => $key_chords];

        // Minor keys
        $minor_scale = get_scale($note, 'minor');
        $chord_qualities = ['minor', 'diminished', 'major', 'minor', 'minor', 'major', 'major'];
        $key_chords = [];
        for ($i = 0; $i < 7; $i++) {
            $key_chords[] = $minor_scale[$i] . ' ' . $chord_qualities[$i];
        }
        $keys[$note . 'm'] = ['scale_type' => 'minor', 'chords' => $key_chords];
    }

    return $keys;
}

function get_function($note, $key) {
    // Map the chord root note to its function in the key
    $key_root = substr($key, 0, -1);
    $key_type = substr($key, -1); // 'M' or 'm'

    $scale_degrees = get_scale_degrees($key_root, $key_type);

    $note_normalized = normalize_note_name($note);
    $key_scale = array_keys($scale_degrees);

    $index = array_search($note_normalized, $key_scale);
    if ($index === false) {
        return 'Non-diatonic';
    }

    return $scale_degrees[$note_normalized];
}

function get_scale_degrees($key_root, $key_type) {
    // Generate scale degrees for the key
    if ($key_type == 'M') {
        $scale = get_scale($key_root, 'major');
        return [
            $scale[0] => 'Tonic (I)',
            $scale[1] => 'Supertonic (ii)',
            $scale[2] => 'Mediant (iii)',
            $scale[3] => 'Subdominant (IV)',
            $scale[4] => 'Dominant (V)',
            $scale[5] => 'Submediant (vi)',
            $scale[6] => 'Leading Tone (vii°)',
        ];
    } elseif ($key_type == 'm') {
        $scale = get_scale($key_root, 'minor');
        return [
            $scale[0] => 'Tonic (i)',
            $scale[1] => 'Supertonic (ii°)',
            $scale[2] => 'Mediant (III)',
            $scale[3] => 'Subdominant (iv)',
            $scale[4] => 'Dominant (v)',
            $scale[5] => 'Submediant (VI)',
            $scale[6] => 'Subtonic (VII)',
        ];
    }
}

function get_scale($root, $scale_type) {
    // Generate the major or minor scale for the given root note
    $notes_sharp = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];
    $notes_flat  = ['C', 'Db', 'D', 'Eb', 'E', 'F', 'Gb', 'G', 'Ab', 'A', 'Bb', 'B'];

    $use_sharps = in_array($root, ['C', 'G', 'D', 'A', 'E', 'B', 'F#', 'C#']);

    $notes = $use_sharps ? $notes_sharp : $notes_flat;
    $root_index = array_search($root, $notes);

    if ($scale_type == 'major') {
        // Major scale intervals: W W H W W W H
        $intervals = [2, 2, 1, 2, 2, 2, 1];
    } elseif ($scale_type == 'minor') {
        // Natural minor scale intervals: W H W W H W W
        $intervals = [2, 1, 2, 2, 1, 2, 2];
    }

    $scale = [$notes[$root_index]];
    foreach ($intervals as $interval) {
        $root_index = ($root_index + $interval) % 12;
        $scale[] = $notes[$root_index];
    }

    // Remove the octave duplication
    array_pop($scale);

    // Normalize note names
    foreach ($scale as &$note) {
        $note = normalize_note_name($note);
    }

    return $scale;
}

function normalize_note_name($note) {
    // Convert note names to a standard format (e.g., Db to C#)
    $enharmonics = [
        'Bb' => 'A#', 'Cb' => 'B',  'Db' => 'C#', 'Eb' => 'D#', 'Fb' => 'E',
        'Gb' => 'F#', 'Ab' => 'G#', 'E#' => 'F',  'B#' => 'C',  'A#' => 'A#',
        'D#' => 'D#', 'G#' => 'G#',
    ];

    return $enharmonics[$note] ?? $note;
}

function get_suggested_scale($key, $scale_type) {
    // Get the suggested scale based on the key and scale type
    $key_root = substr($key, 0, -1);
    $key_type = substr($key, -1); // 'M' or 'm'

    if ($scale_type == 'chromatic') {
        return get_chromatic_scale();
    } else {
        if ($key_type == 'M') {
            return get_scale($key_root, 'major');
        } elseif ($key_type == 'm') {
            return get_scale($key_root, 'minor');
        }
    }
}

function get_chromatic_scale() {
    return ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];
}
