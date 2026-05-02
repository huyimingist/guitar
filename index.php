<?php
// /var/www/html/guitar/index.php

$notes = [
    // string 1: high E
    ['string' => 1, 'fret' => 0, 'note' => 'E'],
    ['string' => 1, 'fret' => 1, 'note' => 'F'],
    ['string' => 1, 'fret' => 3, 'note' => 'G'],

    // string 2: B
    ['string' => 2, 'fret' => 0, 'note' => 'B'],
    ['string' => 2, 'fret' => 1, 'note' => 'C'],
    ['string' => 2, 'fret' => 3, 'note' => 'D'],

    // string 3: G
    ['string' => 3, 'fret' => 0, 'note' => 'G'],
    ['string' => 3, 'fret' => 2, 'note' => 'A'],

    // string 4: D
    ['string' => 4, 'fret' => 0, 'note' => 'D'],
    ['string' => 4, 'fret' => 2, 'note' => 'E'],
    ['string' => 4, 'fret' => 3, 'note' => 'F'],

    // string 5: A
    ['string' => 5, 'fret' => 0, 'note' => 'A'],
    ['string' => 5, 'fret' => 2, 'note' => 'B'],
    ['string' => 5, 'fret' => 3, 'note' => 'C'],

    // string 6: low E
    ['string' => 6, 'fret' => 0, 'note' => 'E'],
    ['string' => 6, 'fret' => 1, 'note' => 'F'],
    ['string' => 6, 'fret' => 3, 'note' => 'G'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Guitar String Notes Practice</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #f5f5f2;
    font-family: Arial, Helvetica, sans-serif;
    color: #222;
}

.app {
    width: 100vw;
    height: 100vh;
    display: flex;
    flex-direction: column;
}

.board-area {
    flex: 1;
    padding: 10px;
}

#fretboardCanvas {
    width: 100%;
    height: 100%;
    display: block;
    background: #ffffff;
    border: 1px solid #ddd;
}

.controls {
    min-height: 86px;
    padding: 10px 14px;
    background: #fff;
    border-top: 1px solid #ddd;
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

button {
    padding: 10px 24px;
    border: 0;
    border-radius: 999px;
    background: #111;
    color: #fff;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
}

button.running {
    background: #b21f1f;
}

label {
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 6px;
}

input[type="checkbox"] {
    width: 20px;
    height: 20px;
}

.slider-box {
    display: flex;
    align-items: center;
    gap: 8px;
}

input[type="range"] {
    width: 220px;
}

#durationText,
#currentInfo {
    font-weight: bold;
}

.current {
    font-size: 18px;
}

@media (max-width: 700px) {
    .controls {
        align-items: flex-start;
        gap: 10px;
    }

    button,
    label,
    .current {
        font-size: 16px;
    }

    .slider-box {
        width: 100%;
    }

    input[type="range"] {
        flex: 1;
    }
}
</style>
</head>

<body>
<div style="padding:8px 12px;background:#fff;border-bottom:1px solid #ddd;font-size:16px;">
    <a href="index.php" style="font-weight:bold;color:#111;text-decoration:none;margin-right:16px;">Fretboard Practice</a>
    <a href="staff.php" style="font-weight:bold;color:#111;text-decoration:none;">Staff Practice</a>
</div>
<div class="app">

    <div class="board-area">
        <canvas id="fretboardCanvas"></canvas>
    </div>

    <div class="controls">
        <button id="startStopBtn">Start</button>

        <label>
            <input type="checkbox" id="showNamesCheck" checked>
            Show note names
        </label>

        <div class="slider-box">
            <span>Duration</span>
            <input type="range" id="durationSlider" min="300" max="15000" step="100" value="1000">
            <span id="durationText">1.0s</span>
        </div>

        <div class="current">
            Current:
            <span id="currentInfo">---</span>
        </div>
    </div>

</div>

<script>
const notes = <?php echo json_encode($notes, JSON_PRETTY_PRINT); ?>;

const canvas = document.getElementById("fretboardCanvas");
const ctx = canvas.getContext("2d");

const startStopBtn = document.getElementById("startStopBtn");
const showNamesCheck = document.getElementById("showNamesCheck");
const durationSlider = document.getElementById("durationSlider");
const durationText = document.getElementById("durationText");
const currentInfo = document.getElementById("currentInfo");

let running = false;
let timer = null;
let currentNote = null;
let lastIndex = -1;

function resizeCanvas() {
    const rect = canvas.getBoundingClientRect();

    canvas.width = rect.width * window.devicePixelRatio;
    canvas.height = rect.height * window.devicePixelRatio;

    ctx.setTransform(
        window.devicePixelRatio,
        0,
        0,
        window.devicePixelRatio,
        0,
        0
    );

    drawBoard();
}

function getBoardSize() {
    return {
        w: canvas.getBoundingClientRect().width,
        h: canvas.getBoundingClientRect().height
    };
}

function getDuration() {
    return Number(durationSlider.value);
}

function updateDurationText() {
    durationText.textContent = (getDuration() / 1000).toFixed(1) + "s";
}

function getStringY(stringNumber) {
    const { h } = getBoardSize();

    const top = h * 0.15;
    const bottom = h * 0.85;
    const gap = (bottom - top) / 5;

    return top + (stringNumber - 1) * gap;
}

function getFretX(fretNumber) {
    const { w } = getBoardSize();

    const left = w * 0.12;
    const right = w * 0.94;

    const nutX = left;
    const fret1X = w * 0.35;
    const fret2X = w * 0.58;
    const fret3X = w * 0.81;
    const endX = right;

    if (fretNumber === 0) {
        return left * 0.55;
    }

    if (fretNumber === 1) {
        return (nutX + fret1X) / 2;
    }

    if (fretNumber === 2) {
        return (fret1X + fret2X) / 2;
    }

    if (fretNumber === 3) {
        return (fret2X + fret3X) / 2;
    }

    return (fret3X + endX) / 2;
}

function drawBoard() {
    const { w, h } = getBoardSize();

    ctx.clearRect(0, 0, w, h);

    ctx.fillStyle = "#ffffff";
    ctx.fillRect(0, 0, w, h);

    const left = w * 0.12;
    const right = w * 0.94;

    const topY = getStringY(1);
    const bottomY = getStringY(6);

    const nutX = left;
    const fret1X = w * 0.35;
    const fret2X = w * 0.58;
    const fret3X = w * 0.81;
    const endX = right;

    // fret labels
    ctx.fillStyle = "#111";
    ctx.font = "bold 22px Arial";
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";

    ctx.fillText("OPEN", getFretX(0), h * 0.06);
    ctx.fillText("1", getFretX(1), h * 0.06);
    ctx.fillText("2", getFretX(2), h * 0.06);
    ctx.fillText("3", getFretX(3), h * 0.06);

    // strings
    for (let s = 1; s <= 6; s++) {
        const y = getStringY(s);

        ctx.beginPath();
        ctx.moveTo(left * 0.35, y);
        ctx.lineTo(right, y);

        ctx.strokeStyle = "#b32626";
        ctx.lineWidth = 4 + s * 0.8;
        ctx.lineCap = "round";
        ctx.stroke();
    }

    // nut
    ctx.beginPath();
    ctx.moveTo(nutX, topY - 25);
    ctx.lineTo(nutX, bottomY + 25);
    ctx.strokeStyle = "#111";
    ctx.lineWidth = 14;
    ctx.lineCap = "round";
    ctx.stroke();

    // frets
    [fret1X, fret2X, fret3X, endX].forEach(x => {
        ctx.beginPath();
        ctx.moveTo(x, topY - 25);
        ctx.lineTo(x, bottomY + 25);
        ctx.strokeStyle = "#b32626";
        ctx.lineWidth = 8;
        ctx.lineCap = "round";
        ctx.stroke();
    });

    // string labels on left
    const labels = ["1st E", "2nd B", "3rd G", "4th D", "5th A", "6th E"];
    ctx.fillStyle = "#333";
    ctx.font = "bold 16px Arial";
    ctx.textAlign = "left";

    for (let s = 1; s <= 6; s++) {
        ctx.fillText(labels[s - 1], 12, getStringY(s));
    }

    if (currentNote) {
        drawNoteDot(currentNote);
    }
}

function drawNoteDot(item) {
    const x = getFretX(item.fret);
    const y = getStringY(item.string);

    const { w } = getBoardSize();
    const r = Math.max(24, Math.min(44, w * 0.035));

    ctx.beginPath();
    ctx.arc(x, y, r, 0, Math.PI * 2);
    ctx.fillStyle = "#111";
    ctx.fill();

    ctx.lineWidth = 5;
    ctx.strokeStyle = "#ffffff";
    ctx.stroke();

    if (showNamesCheck.checked) {
        ctx.fillStyle = "#ffffff";
        ctx.font = `bold ${r * 1.1}px Arial`;
        ctx.textAlign = "center";
        ctx.textBaseline = "middle";
        ctx.fillText(item.note, x, y + 1);
    }
}

function pickRandomNote() {
    let index;

    do {
        index = Math.floor(Math.random() * notes.length);
    } while (notes.length > 1 && index === lastIndex);

    lastIndex = index;
    return notes[index];
}

function showNextNote() {
    if (!running) {
        return;
    }

    currentNote = pickRandomNote();

    currentInfo.textContent =
        `${currentNote.note} / string ${currentNote.string} / fret ${currentNote.fret}`;

    drawBoard();

    timer = setTimeout(() => {
        currentNote = null;
        drawBoard();

        timer = setTimeout(() => {
            showNextNote();
        }, 120);

    }, getDuration());
}

function startPractice() {
    running = true;
    startStopBtn.textContent = "Stop";
    startStopBtn.classList.add("running");
    showNextNote();
}

function stopPractice() {
    running = false;

    if (timer) {
        clearTimeout(timer);
        timer = null;
    }

    currentNote = null;
    currentInfo.textContent = "---";

    startStopBtn.textContent = "Start";
    startStopBtn.classList.remove("running");

    drawBoard();
}

function togglePractice() {
    if (running) {
        stopPractice();
    } else {
        startPractice();
    }
}

startStopBtn.addEventListener("click", togglePractice);

showNamesCheck.addEventListener("change", drawBoard);

durationSlider.addEventListener("input", () => {
    updateDurationText();
});

window.addEventListener("resize", resizeCanvas);

updateDurationText();
resizeCanvas();
</script>

</body>
</html>