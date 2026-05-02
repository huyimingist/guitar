<?php
// /var/www/html/guitar/staff.php

/*
    Staff note range for guitar first position:

    E F G A B C D E F G A B C D E F G

    step 0  = lowest E, space below 3rd ledger line
    step 1  = F, 3rd ledger line below staff
    step 2  = G, space between 3rd and 2nd ledger lines
    step 3  = A, 2nd ledger line below staff
    step 4  = B, space between 2nd and 1st ledger lines
    step 5  = C, 1st ledger line below staff
    step 6  = D, space below bottom staff line
    step 7  = E, bottom staff line
    step 8  = F, first space
    step 9  = G, second staff line
    step 10 = A, second space
    step 11 = B, third staff line
    step 12 = C, third space
    step 13 = D, fourth staff line
    step 14 = E, fourth space
    step 15 = F, top staff line
    step 16 = highest G, space above top staff line
*/

$notes = [
    ['note' => 'E', 'step' => 0],
    ['note' => 'F', 'step' => 1],
    ['note' => 'G', 'step' => 2],
    ['note' => 'A', 'step' => 3],
    ['note' => 'B', 'step' => 4],
    ['note' => 'C', 'step' => 5],
    ['note' => 'D', 'step' => 6],
    ['note' => 'E', 'step' => 7],
    ['note' => 'F', 'step' => 8],
    ['note' => 'G', 'step' => 9],
    ['note' => 'A', 'step' => 10],
    ['note' => 'B', 'step' => 11],
    ['note' => 'C', 'step' => 12],
    ['note' => 'D', 'step' => 13],
    ['note' => 'E', 'step' => 14],
    ['note' => 'F', 'step' => 15],
    ['note' => 'G', 'step' => 16],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Notes Practice</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #f5f5f2;
    color: #222;
    font-family: Arial, Helvetica, sans-serif;
}

.app {
    width: 100vw;
    height: 100vh;
    display: flex;
    flex-direction: column;
}

.top-links {
    padding: 8px 12px;
    background: #fff;
    border-bottom: 1px solid #ddd;
    font-size: 16px;
}

.top-links a {
    color: #111;
    font-weight: bold;
    text-decoration: none;
    margin-right: 16px;
}

.board-area {
    flex: 1;
    padding: 10px;
}

#staffCanvas {
    width: 100%;
    height: 100%;
    display: block;
    background: #fff;
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
    .current,
    .top-links {
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

<div class="app">

    <div class="top-links">
        <a href="index.php">Fretboard Practice</a>
        <a href="staff.php">Staff Practice</a>
    </div>

    <div class="board-area">
        <canvas id="staffCanvas"></canvas>
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

const canvas = document.getElementById("staffCanvas");
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

    drawStaff();
}

function getSize() {
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

function getStaffGeometry() {
    const { w, h } = getSize();

    /*
        lineGap = distance between two staff lines.

        We need enough room below the staff because the lowest E
        sits under the 3rd ledger line.
    */

    const lineGap = Math.max(30, Math.min(58, h * 0.075));
    const halfGap = lineGap / 2;

    /*
        Put the 5-line staff slightly above the center.
        This leaves good space for the 3 lower ledger lines.
    */

    const staffTop = h * 0.18;
    const staffBottom = staffTop + lineGap * 4;

    const left = w * 0.07;
    const right = w * 0.94;

    return {
        w,
        h,
        lineGap,
        halfGap,
        staffTop,
        staffBottom,
        left,
        right
    };
}

function getNoteY(step) {
    const g = getStaffGeometry();

    /*
        Correct mapping:

        step 0  = low E below 3rd ledger line
        step 1  = F on 3rd ledger line
        step 3  = A on 2nd ledger line
        step 5  = C on 1st ledger line
        step 7  = E on bottom staff line
        step 9  = G on second staff line
        step 11 = B on third staff line
        step 13 = D on fourth staff line
        step 15 = F on top staff line
        step 16 = high G above top staff line

        Every step moves by half a staff-line gap.
    */

    return g.staffBottom - ((step - 7) * g.halfGap);
}

function getNoteX() {
    const g = getStaffGeometry();

    /*
        Keep the note around the center of the staff.
        This app is for recognizing vertical staff position,
        not reading rhythm horizontally.
    */

    return g.w * 0.52;
}

function drawStaff() {
    const g = getStaffGeometry();

    ctx.clearRect(0, 0, g.w, g.h);

    ctx.fillStyle = "#ffffff";
    ctx.fillRect(0, 0, g.w, g.h);

    drawFiveLines(g);

    if (currentNote) {
        drawNeededLedgerLines(currentNote, g);
        drawNote(currentNote, g);
    }

    drawRangeHint(g);
}

function drawFiveLines(g) {
    ctx.strokeStyle = "#b32626";
    ctx.lineWidth = 5;
    ctx.lineCap = "round";

    for (let i = 0; i < 5; i++) {
        const y = g.staffTop + i * g.lineGap;

        ctx.beginPath();
        ctx.moveTo(g.left, y);
        ctx.lineTo(g.right, y);
        ctx.stroke();
    }
}

function drawNeededLedgerLines(item, g) {
    const x = getNoteX();

    const ledgerHalfLength = Math.max(48, Math.min(95, g.w * 0.075));
    const left = x - ledgerHalfLength;
    const right = x + ledgerHalfLength;

    ctx.strokeStyle = "#b32626";
    ctx.lineWidth = 5;
    ctx.lineCap = "round";

    /*
        Lower ledger lines:

        step 5 = C, first ledger line below staff
        step 3 = A, second ledger line below staff
        step 1 = F, third ledger line below staff

        The lowest E, step 0, sits below the third ledger line.
    */

    const belowLedgerSteps = [5, 3, 1];

    belowLedgerSteps.forEach(ledgerStep => {
        if (item.step <= ledgerStep) {
            const y = getNoteY(ledgerStep);

            ctx.beginPath();
            ctx.moveTo(left, y);
            ctx.lineTo(right, y);
            ctx.stroke();
        }
    });

    /*
        Upper ledger lines:

        Current highest note is high G, step 16.
        It is in the space above the top staff line,
        so it does not need an upper ledger line.

        If later you add A or higher above this G,
        then add upper ledger logic here.
    */
}

function drawNote(item, g) {
    const x = getNoteX();
    const y = getNoteY(item.step);

    const noteWidth = Math.max(36, Math.min(64, g.w * 0.045));
    const noteHeight = noteWidth * 0.72;

    ctx.save();

    /*
        Draw a simple black oval note head.
    */

    ctx.translate(x, y);
    ctx.rotate(-0.18);

    ctx.beginPath();
    ctx.ellipse(
        0,
        0,
        noteWidth / 2,
        noteHeight / 2,
        0,
        0,
        Math.PI * 2
    );
    ctx.fillStyle = "#111";
    ctx.fill();

    ctx.restore();

    if (showNamesCheck.checked) {
        ctx.fillStyle = "#fff";
        ctx.font = `bold ${Math.floor(noteHeight * 0.85)}px Arial`;
        ctx.textAlign = "center";
        ctx.textBaseline = "middle";
        ctx.fillText(item.note, x, y + 1);
    }
}

function drawRangeHint(g) {
    ctx.fillStyle = "#555";
    ctx.font = "bold 16px Arial";
    ctx.textAlign = "left";
    ctx.textBaseline = "middle";

    ctx.fillText(
        "Range: E F G A B C D E F G A B C D E F G",
        g.left,
        g.h * 0.075
    );
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
        `${currentNote.note} / step ${currentNote.step}`;

    drawStaff();

    timer = setTimeout(() => {
        currentNote = null;
        drawStaff();

        timer = setTimeout(() => {
            showNextNote();
        }, 120);

    }, getDuration());
}

function startPractice() {
    if (running) {
        return;
    }

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

    drawStaff();
}

function togglePractice() {
    if (running) {
        stopPractice();
    } else {
        startPractice();
    }
}

startStopBtn.addEventListener("click", togglePractice);

showNamesCheck.addEventListener("change", drawStaff);

durationSlider.addEventListener("input", updateDurationText);

window.addEventListener("resize", resizeCanvas);

updateDurationText();
resizeCanvas();
</script>

</body>
</html>