<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NIT Timetable System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
      
/* ===================================
   NIT Timetable System - Enhanced CSS
   =================================== */

/* ========== GLOBAL STYLES ========== */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 2rem 1rem;
    color: #1f2937;
}

/* ========== CONTAINER ========== */
.container_timetable {
    max-width: 1280px;
    margin: 0 auto;
    padding: 1rem;
}

/* ========== HEADER STYLES ========== */
header {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 20px;
    padding: 2.5rem 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    position: relative;
    overflow: hidden;
    animation: slideDown 0.6s ease-out;
}

header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
    animation: shimmer 3s infinite;
}

@keyframes shimmer {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

header h1 {
    font-size: 2.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #0b2241, #1e3a8a);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.75rem;
    letter-spacing: -0.5px;
}

header p {
    color: #4b5563;
    font-size: 1.125rem;
    font-weight: 500;
    margin-top: 0.5rem;
}

header p:last-child {
    font-size: 0.875rem;
    color: #9ca3af;
    font-weight: 400;
}

/* ========== ACTION BUTTONS ========== */
.action-buttons {
    margin: 1.5rem 0;
    animation: fadeIn 0.8s ease-out;
}

.action-buttons a {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    padding: 12px 28px;
    text-decoration: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
    transition: all 0.3s ease;
}

.action-buttons a:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.5);
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
}

.action-buttons a:active {
    transform: translateY(0);
}

/* ========== VIEW TOGGLE BUTTONS ========== */
.view-toggle-btn {
    padding: 14px 32px;
    font-size: 1rem;
    font-weight: 600;
    border-radius: 50px;
    border: none;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.view-toggle-btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.view-toggle-btn:hover::before {
    width: 300px;
    height: 300px;
}

.view-toggle-btn:active {
    transform: scale(0.95);
}

/* ========== DEPARTMENT BUTTONS ========== */
.dept-btn {
    padding: 12px 24px;
    font-weight: 600;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    position: relative;
    overflow: hidden;
}

.dept-btn::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 5px;
    height: 5px;
    background: rgba(255, 255, 255, 0.5);
    opacity: 0;
    border-radius: 50%;
    transform: translate(-50%, -50%) scale(1);
    transition: width 0.5s, height 0.5s, opacity 0.5s;
}

.dept-btn:hover::after {
    width: 200px;
    height: 200px;
    opacity: 0;
}

.dept-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
}

/* ========== VIEW CONTAINERS ========== */
#student-view,
#teacher-view {
    background: white;
    padding: 2rem;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

#student-view h2,
#teacher-view h2 {
    color: #1f2937;
    font-size: 1.875rem;
    margin-bottom: 1.5rem;
    position: relative;
    padding-bottom: 0.75rem;
}

#student-view h2::after,
#teacher-view h2::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60px;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
    border-radius: 2px;
}

/* ========== FACULTY SELECT ========== */
#faculty-select {
    width: 100%;
    max-width: 500px;
    padding: 14px 18px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 500;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

#faculty-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

#faculty-select:hover {
    border-color: #9ca3af;
}

/* ========== TIMETABLE TABLE ========== */
.timetable-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    overflow: hidden;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
    animation: fadeIn 0.8s ease-out;
}

.timetable-table th,
.timetable-table td {
    padding: 16px 12px;
    text-align: center;
    font-size: 0.875rem;
    border: 1px solid #e5e7eb;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.timetable-table th {
    background: linear-gradient(135deg, #0b2241, #1e3a8a);
    color: white;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.8rem;
    position: sticky;
    top: 0;
    z-index: 10;
}

.timetable-table tbody tr {
    transition: all 0.3s ease;
}

.timetable-table tbody tr:nth-child(even) {
    background-color: #f9fafb;
}

.timetable-table tbody tr:hover {
    background-color: #f3f4f6;
    transform: scale(1.01);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

/* ========== CELL HOVER EFFECTS ========== */
.timetable-table td:hover:not(.day-cell):not(.break-cell):not(.room-cell):not(.lunch-cell) {
    transform: scale(1.08);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    z-index: 15;
    cursor: pointer;
}

.timetable-table td:hover::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0));
    pointer-events: none;
    border-radius: 4px;
}

/* ========== CELL TYPE STYLES ========== */
.day-cell {
    font-weight: 800;
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    color: #047857;
    font-size: 0.95rem;
    letter-spacing: 1px;
}

.room-cell {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #92400e;
    font-weight: 700;
}

.class-cell {
    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
    color: #1e40af;
    font-weight: 600;
    cursor: pointer;
}

.practical {
    background: linear-gradient(135deg, #fce7f3, #fbcfe8);
    color: #be185d;
    font-weight: 600;
    cursor: pointer;
}

.break-cell,
.lunch-cell {
    background: linear-gradient(135deg, #fed7aa, #fdba74);
    color: #c2410c;
    font-weight: 800;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
}

.library {
    background: linear-gradient(135deg, #cffafe, #a5f3fc);
    color: #0e7490;
    font-weight: 600;
    cursor: pointer;
}

.data-missing {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    color: #991b1b;
    font-style: italic;
    font-weight: 500;
}

/* T&P Session styling */
.bg-yellow-100 {
    background: linear-gradient(135deg, #fef9c3, #fef08a);
}

.text-yellow-800 {
    color: #854d0e;
}

/* ========== STICKY COLUMNS ========== */
.sticky-col {
    position: sticky;
    left: 0;
    z-index: 5;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
}

.timetable-table th.sticky-col {
    z-index: 20;
}

/* ========== RESPONSIVE DESIGN ========== */
@media (max-width: 1024px) {
    .container_timetable {
        padding: 0.75rem;
    }
    
    header {
        padding: 2rem 1.5rem;
    }
    
    header h1 {
        font-size: 2rem;
    }
}

@media (max-width: 768px) {
    body {
        padding: 1rem 0.5rem;
    }
    
    header {
        border-radius: 16px;
        padding: 1.5rem 1rem;
    }
    
    header h1 {
        font-size: 1.5rem;
    }
    
    header p {
        font-size: 0.95rem;
    }
    
    .view-toggle-btn {
        padding: 12px 24px;
        font-size: 0.9rem;
    }
    
    .dept-btn {
        padding: 10px 20px;
        font-size: 0.875rem;
    }
    
    #student-view,
    #teacher-view {
        padding: 1.5rem 1rem;
        border-radius: 16px;
    }
    
    .timetable-table {
        display: block;
        overflow-x: auto;
        border-radius: 12px;
    }
    
    .timetable-table th,
    .timetable-table td {
        padding: 12px 10px;
        font-size: 0.8rem;
        white-space: nowrap;
    }
}

@media (max-width: 480px) {
    header h1 {
        font-size: 1.25rem;
    }
    
    header p {
        font-size: 0.85rem;
    }
    
    .view-toggle-btn {
        padding: 10px 20px;
        font-size: 0.85rem;
    }
    
    .timetable-table th,
    .timetable-table td {
        padding: 10px 8px;
        font-size: 0.75rem;
    }
}

/* ========== PRINT STYLES ========== */
@media print {
    body {
        background: white;
        padding: 0;
    }
    
    .action-buttons,
    .view-toggle-btn {
        display: none;
    }
    
    .timetable-table {
        box-shadow: none;
    }
    
    .timetable-table td:hover {
        transform: none;
        box-shadow: none;
    }
}

/* ========== LOADING ANIMATION ========== */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.loading {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* ========== SCROLLBAR STYLING ========== */
.timetable-table::-webkit-scrollbar {
    height: 10px;
}

.timetable-table::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}

.timetable-table::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 10px;
}

.timetable-table::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #764ba2, #667eea);
}
    </style>
</head>
<body>

<div class="container_timetable_timetable">

<!-- Action Buttons -->
    <div class="action-buttons" style="margin-top: 20px;">
    <a href="index.php" 
       style="
           background-color: #0d6efd;
           color: white;
           padding: 10px 20px;
           text-decoration: none;
           border-radius: 6px;
           font-size: 15px;
           display: inline-block;
       ">
       Dashboard
    </a>
      </div>
    <header class="text-center py-6 bg-white shadow-lg rounded-xl mb-8">
        <h1 class="text-3xl font-extrabold text-[#0b2241]">NAGPUR INSTITUTE OF TECHNOLOGY</h1>
        <p class="text-lg font-medium text-gray-600 mt-2">B. TECH. FIRST YEAR - FIRST SEMESTER SESSION 2025-26</p>
        <p class="text-sm text-gray-500">Effective from 29/09/2025</p>
    </header>

    <!-- View Toggles -->
    <div class="flex justify-center mb-8 space-x-4">
        <button id="student-view-btn" class="view-toggle-btn px-6 py-3 font-semibold rounded-full shadow-lg transition duration-300 bg-indigo-600 text-white hover:bg-indigo-700">
            Student Timetable (By Dept.)
        </button>
        <button id="teacher-view-btn" class="view-toggle-btn px-6 py-3 font-semibold rounded-full shadow-lg transition duration-300 bg-gray-200 text-gray-700 hover:bg-gray-300">
            Teacher Timetable (By Faculty)
        </button>
    </div>

    <!-- Main Content Area -->
    <div id="student-view" class="bg-white p-6 rounded-xl shadow-xl hidden">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Student Timetable View</h2>
        <div class="mb-6 flex flex-wrap gap-3">
            <!-- Department Buttons -->
            <button onclick="renderStudentTimetable('ACSE', this)" class="dept-btn px-4 py-2 bg-blue-500 text-white font-medium rounded-lg hover:bg-blue-600 transition duration-150 shadow-md">ACSE</button>
            <button onclick="renderStudentTimetable('BCSE', this)" class="dept-btn px-4 py-2 bg-green-500 text-white font-medium rounded-lg hover:bg-green-600 transition duration-150 shadow-md">BCSE</button>
            <button onclick="renderStudentTimetable('IT', this)" class="dept-btn px-4 py-2 bg-yellow-500 text-white font-medium rounded-lg hover:bg-yellow-600 transition duration-150 shadow-md">IT</button>
            <button onclick="renderStudentTimetable('EE', this)" class="dept-btn px-4 py-2 bg-red-500 text-white font-medium rounded-lg hover:bg-red-600 transition duration-150 shadow-md">EE</button>
            <button onclick="renderStudentTimetable('ME', this)" class="dept-btn px-4 py-2 bg-purple-500 text-white font-medium rounded-lg hover:bg-purple-600 transition duration-150 shadow-md">ME</button>
            <button onclick="renderStudentTimetable('CE', this)" class="dept-btn px-4 py-2 bg-pink-500 text-white font-medium rounded-lg hover:bg-pink-600 transition duration-150 shadow-md">CE</button>
        </div>
        <div id="student-timetable-output" class="overflow-x-auto">
            <p class="text-center text-gray-500 p-8 border-2 border-dashed rounded-lg">
                Please select a Department above to view its day-wise timetable.
            </p>
        </div>
    </div>

    <div id="teacher-view" class="bg-white p-6 rounded-xl shadow-xl hidden">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Teacher Timetable View</h2>
        <div class="mb-6">
            <label for="faculty-select" class="block text-sm font-medium text-gray-700 mb-2">Select Faculty Member:</label>
            <select id="faculty-select" onchange="renderTeacherTimetable(this.value)" class="w-full md:w-1/2 p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition duration-150">
                <option value="">-- Select a Teacher --</option>
                <!-- Options populated by JS -->
            </select>
        </div>
        <div id="teacher-timetable-output" class="overflow-x-auto">
            <p class="text-center text-gray-500 p-8 border-2 border-dashed rounded-lg">
                Please select a Faculty Member from the dropdown to see their weekly schedule.
            </p>
        </div>
    </div>
</div>

<script>
    // --- TIMETABLE DATA STRUCTURE ---
    // The raw data from the provided table, structured for easy processing
    const TIMETABLE_DATA = [
        // MON
        { day: "MON", sec: "ACSE", room: "B-104", p1: "E.CHEM(B1)/ BEE(B2) PRACTICAL (Prac)", p2: "E.CHEM(B1)/ BEE(B2) PRACTICAL (Prac)", p3: "A. MATH-I (MD)", p4: "PSC(AS)", p5: "T&P SESSION (Other)", p6: "PSC(B1,B2) PRACTICAL (Prac)", faculty: { 'A. MATH-I (MD)': 'MRS. MONA DANGE', 'PSC(AS)': 'MR. AYAZ SHAIKH', 'T&P SESSION (Other)': 'N/A', 'PSC(B1,B2) PRACTICAL (Prac)': 'MR. AYAZ SHAIKH', 'E.CHEM(B1)/ BEE(B2) PRACTICAL (Prac)': 'DR. SONIKA KOCHHAR' } },
        { day: "MON", sec: "BCSE", room: "B-105", p1: "PSC(AS)", p2: "A. MATH-I (MD)", p3: "E.CHEM(SK)", p4: "BEE (RK)", p5: "CS (B1)/CC (B2) PRACTICAL (Prac)", p6: "CS (B1)/CC (B2) PRACTICAL (Prac)", faculty: { 'PSC(AS)': 'MR. AYAZ SHAIKH', 'A. MATH-I (MD)': 'MRS. MONA DANGE', 'E.CHEM(SK)': 'DR. SONIKA KOCHHAR', 'BEE (RK)': 'MR. RAHUL KADAM', 'CS (B1)/CC (B2) PRACTICAL (Prac)': 'MRS. HITASHI CHAUHAN' } },
        { day: "MON", sec: "IT", room: "B-106", p1: "E.CHEM(MJ)", p2: "A. MATH-I (VR)", p3: "PSC(AS)", p4: "CS(HC)", p5: "CS (B1)/CC (B2) PRACTICAL (Prac)", p6: "CS (B1)/CC (B2) PRACTICAL (Prac)", faculty: { 'E.CHEM(MJ)': 'DR. MEGHNA JUMBHLE', 'A. MATH-I (VR)': 'MRS.VIDYA RAUT', 'PSC(AS)': 'MR. AYAZ SHAIKH', 'CS(HC)': 'MRS. HITASHI CHAUHAN', 'CS (B1)/CC (B2) PRACTICAL (Prac)': 'MRS. HITASHI CHAUHAN' } },
        { day: "MON", sec: "EE", room: "B-107", p1: "A.MATH-I(PD)", p2: "A.PHY(JB)", p3: "CS(MS)", p4: "BEE (RD)", p5: "A.PHY(B1)/EG (B2) PRACTICAL (Prac)", p6: "A.PHY(B1)/EG (B2) PRACTICAL (Prac)", faculty: { 'A.MATH-I(PD)': 'MR. PRASHANT DANGE', 'A.PHY(JB)': 'DR. JITENDRA BHAISWAR', 'CS(MS)': 'DR. MOHAMMAD SABIR', 'BEE (RD)': 'MRS. RACHANA DAGA', 'A.PHY(B1)/EG (B2) PRACTICAL (Prac)': 'DR. JITENDRA BHAISWAR' } },
        { day: "MON", sec: "ME", room: "B-206", p1: "CP (AS)", p2: "A.PHY(DM)", p3: "A.MATH-I(PD)", p4: "EG(SK)", p5: "EG (B1,B2) PRACTICAL (Prac)", p6: "EG (B1,B2) PRACTICAL (Prac)", faculty: { 'CP (AS)': 'MISS. AYUSHI SHARMA', 'A.PHY(DM)': 'MR. DHIRAJ MEGHE', 'A.MATH-I(PD)': 'MR. PRASHANT DANGE', 'EG(SK)': 'MR. SAMRAT KAVISHWAR', 'EG (B1,B2) PRACTICAL (Prac)': 'MR. SAMRAT KAVISHWAR' } },
        { day: "MON", sec: "CE", room: "B-207", p1: "CS (B1)/CC (B2) PRACTICAL (Prac)", p2: "CS (B1)/CC (B2) PRACTICAL (Prac)", p3: "A. MATH-I (VR)", p4: "EG(GK)", p5: "FOV(AK)", p6: "TGM.LIBRARY (Library)", faculty: { 'CS (B1)/CC (B2) PRACTICAL (Prac)': 'DR. MOHAMMAD SABIR', 'A. MATH-I (VR)': 'MRS.VIDYA RAUT', 'EG(GK)': 'MR. GIRISAN KHAN', 'FOV(AK)': 'MR. AMIT KHARWADE', 'TGM.LIBRARY (Library)': 'N/A' } },

        // TUE
        { day: "TUE", sec: "ACSE", room: "B-104", p1: "E.CHEM(B1)/ BEE(B1) PRACTICAL (Prac)", p2: "E.CHEM(B1)/ BEE(B1) PRACTICAL (Prac)", p3: "PSC(AS)", p4: "CS(HC)", p5: "A. MATH-I (MD)", p6: "TGM.LIBRARY (Library)", faculty: { 'E.CHEM(B1)/ BEE(B1) PRACTICAL (Prac)': 'DR. SONIKA KOCHHAR', 'PSC(AS)': 'MR. AYAZ SHAIKH', 'CS(HC)': 'MRS. HITASHI CHAUHAN', 'A. MATH-I (MD)': 'MRS. MONA DANGE', 'TGM.LIBRARY (Library)': 'N/A' } },
        { day: "TUE", sec: "BCSE", room: "B-105", p1: "PSC(AS)", p2: "A. MATH-I (MD)", p3: "BEE (RK)", p4: "E.CHEM(SK)", p5: "CS (B1)/CC (B2) PRACTICAL (Prac)", p6: "CS (B1)/CC (B2) PRACTICAL (Prac)", faculty: { 'PSC(AS)': 'MR. AYAZ SHAIKH', 'A. MATH-I (MD)': 'MRS. MONA DANGE', 'BEE (RK)': 'MR. RAHUL KADAM', 'E.CHEM(SK)': 'DR. SONIKA KOCHHAR', 'CS (B1)/CC (B2) PRACTICAL (Prac)': 'MR. RAHUL KADAM' } },
        { day: "TUE", sec: "IT", room: "B-106", p1: "E.CHEM(MJ)", p2: "A. MATH-I (VR)", p3: "BEE (TS)", p4: "PSC(AS)", p5: "PSC(B1,B2) PRACTICAL (Prac)", p6: "PSC(B1,B2) PRACTICAL (Prac)", faculty: { 'E.CHEM(MJ)': 'DR. MEGHNA JUMBHLE', 'A. MATH-I (VR)': 'MRS.VIDYA RAUT', 'BEE (TS)': 'MR. TUSHAR SHELKE', 'PSC(AS)': 'MR. AYAZ SHAIKH', 'PSC(B1,B2) PRACTICAL (Prac)': 'MISS. AYUSHI SHARMA' } },
        { day: "TUE", sec: "EE", room: "B-107", p1: "A.MATH-I(PD)", p2: "A.PHY(JB)", p3: "BEE (RD)", p4: "EG(RD)", p5: "A.PHY(B2)/EG (B1) PRACTICAL (Prac)", p6: "A.PHY(B2)/EG (B1) PRACTICAL (Prac)", faculty: { 'A.MATH-I(PD)': 'MR. PRASHANT DANGE', 'A.PHY(JB)': 'DR. JITENDRA BHAISWAR', 'BEE (RD)': 'MRS. RACHANA DAGA', 'EG(RD)': 'MR. ROHAN DESHMUKH', 'A.PHY(B2)/EG (B1) PRACTICAL (Prac)': 'DR. JITENDRA BHAISWAR' } },
        { day: "TUE", sec: "ME", room: "B-206", p1: "CP (AS)", p2: "A.PHY(DM)", p3: "A.MATH-I(PD)", p4: "EG(SK)", p5: "WS(B1,B2) PRACTICAL (Prac)", p6: "WS(B1,B2) PRACTICAL (Prac)", faculty: { 'CP (AS)': 'MISS. AYUSHI SHARMA', 'A.PHY(DM)': 'MR. DHIRAJ MEGHE', 'A.MATH-I(PD)': 'MR. PRASHANT DANGE', 'EG(SK)': 'MR. SAMRAT KAVISHWAR', 'WS(B1,B2) PRACTICAL (Prac)': 'MR. SAMRAT KAVISHWAR' } },
        { day: "TUE", sec: "CE", room: "B-207", p1: "CS (B1)/CC (B2) PRACTICAL (Prac)", p2: "CS (B1)/CC (B2) PRACTICAL (Prac)", p3: "A.PHY(DM)", p4: "CS(MS)", p5: "T&P SESSION (Other)", p6: "T&P SESSION (Other)", faculty: { 'CS (B1)/CC (B2) PRACTICAL (Prac)': 'DR. MOHAMMAD SABIR', 'A.PHY(DM)': 'MR. DHIRAJ MEGHE', 'CS(MS)': 'DR. MOHAMMAD SABIR', 'T&P SESSION (Other)': 'N/A' } },

        // WED
        { day: "WED", sec: "ACSE", room: "B-104", p1: "PSC(AS)", p2: "A. MATH-I (MD)", p3: "E.CHEM(SK)", p4: "BEE (RD)", p5: "PSC(B1,B2) PRACTICAL (Prac)", p6: "PSC(B1,B2) PRACTICAL (Prac)", faculty: { 'PSC(AS)': 'MR. AYAZ SHAIKH', 'A. MATH-I (MD)': 'MRS. MONA DANGE', 'E.CHEM(SK)': 'DR. SONIKA KOCHHAR', 'BEE (RD)': 'MRS. RACHANA DAGA', 'PSC(B1,B2) PRACTICAL (Prac)': 'MR. AYAZ SHAIKH' } },
        { day: "WED", sec: "BCSE", room: "B-105", p1: "CS(HC)", p2: "E.CHEM(SK)", p3: "A. MATH-I (MD)", p4: "PSC(AS)", p5: "CS (B2)/CC (B1) PRACTICAL (Prac)", p6: "CS (B2)/CC (B1) PRACTICAL (Prac)", faculty: { 'CS(HC)': 'MRS. HITASHI CHAUHAN', 'E.CHEM(SK)': 'DR. SONIKA KOCHHAR', 'A. MATH-I (MD)': 'MRS. MONA DANGE', 'PSC(AS)': 'MR. AYAZ SHAIKH', 'CS (B2)/CC (B1) PRACTICAL (Prac)': 'MR. RAHUL KADAM' } },
        { day: "WED", sec: "IT", room: "B-106", p1: "E.CHEM(B1)/ BEE(B2) PRACTICAL (Prac)", p2: "E.CHEM(B1)/ BEE(B2) PRACTICAL (Prac)", p3: "A. MATH-I (VR)", p4: "BEE(TS)", p5: "T&P SESSION (Other)", p6: "T&P SESSION (Other)", faculty: { 'E.CHEM(B1)/ BEE(B2) PRACTICAL (Prac)': 'DR. MEGHNA JUMBHLE', 'A. MATH-I (VR)': 'MRS.VIDYA RAUT', 'BEE(TS)': 'MR. TUSHAR SHELKE', 'T&P SESSION (Other)': 'N/A' } },
        { day: "WED", sec: "EE", room: "B-107", p1: "CS (B1)/CC (B2) PRACTICAL (Prac)", p2: "CS (B1)/CC (B2) PRACTICAL (Prac)", p3: "A.PHY(JB)", p4: "EG(RD)", p5: "A. MATH-I (PD)", p6: "TGM.LIBRARY (Library)", faculty: { 'CS (B1)/CC (B2) PRACTICAL (Prac)': 'DR. MOHAMMAD SABIR', 'A.PHY(JB)': 'DR. JITENDRA BHAISWAR', 'EG(RD)': 'MR. ROHAN DESHMUKH', 'A. MATH-I (PD)': 'MR. PRASHANT DANGE', 'TGM.LIBRARY (Library)': 'N/A' } },
        { day: "WED", sec: "ME", room: "B-206", p1: "A.MATH-I(PD)", p2: "A.PHY(DM)", p3: "CP(AS)", p4: "CS(MS)", p5: "A.PHY(B1)/CC (B2) PRACTICAL (Prac)", p6: "A.PHY(B1)/CC (B2) PRACTICAL (Prac)", faculty: { 'A.MATH-I(PD)': 'MR. PRASHANT DANGE', 'A.PHY(DM)': 'MR. DHIRAJ MEGHE', 'CP(AS)': 'MISS. AYUSHI SHARMA', 'CS(MS)': 'DR. MOHAMMAD SABIR', 'A.PHY(B1)/CC (B2) PRACTICAL (Prac)': 'MR. DHIRAJ MEGHE' } },
        { day: "WED", sec: "CE", room: "B-207", p1: "EG(GK)", p2: "A. MATH-I (VR)", p3: "A.PHY(DM)", p4: "FOV(AK)", p5: "WS(B1)/ FOV(B2) PRACTICAL (Prac)", p6: "WS(B1)/ FOV(B2) PRACTICAL (Prac)", faculty: { 'EG(GK)': 'MR. GIRISAN KHAN', 'A. MATH-I (VR)': 'MRS.VIDYA RAUT', 'A.PHY(DM)': 'MR. DHIRAJ MEGHE', 'FOV(AK)': 'MR. AMIT KHARWADE', 'WS(B1)/ FOV(B2) PRACTICAL (Prac)': 'MR. AMIT KHARWADE' } },

        // THU
        { day: "THU", sec: "ACSE", room: "B-104", p1: "A. MATH-I (MD)", p2: "PSC(AS)", p3: "BEE (RD)", p4: "E.CHEM(SK)", p5: "WT(B1,B2) PRACTICAL (Prac)", p6: "WT(B1,B2) PRACTICAL (Prac)", faculty: { 'A. MATH-I (MD)': 'MRS. MONA DANGE', 'PSC(AS)': 'MR. AYAZ SHAIKH', 'BEE (RD)': 'MRS. RACHANA DAGA', 'E.CHEM(SK)': 'DR. SONIKA KOCHHAR', 'WT(B1,B2) PRACTICAL (Prac)': 'PURNIMA BHUYAR' } },
        { day: "THU", sec: "BCSE", room: "B-105", p1: "E.CHEM(B1)/ BEE(B2) PRACTICAL (Prac)", p2: "E.CHEM(B1)/ BEE(B2) PRACTICAL (Prac)", p3: "PSC(AS)", p4: "BEE (RK)", p5: "A. MATH-I (MD)", p6: "TGM.LIBRARY (Library)", faculty: { 'E.CHEM(B1)/ BEE(B2) PRACTICAL (Prac)': 'DR. SONIKA KOCHHAR', 'PSC(AS)': 'MR. AYAZ SHAIKH', 'BEE (RK)': 'MR. RAHUL KADAM', 'A. MATH-I (MD)': 'MRS. MONA DANGE', 'TGM.LIBRARY (Library)': 'N/A' } },
        { day: "THU", sec: "IT", room: "B-106", p1: "E.CHEM(MJ)", p2: "PSC(AS)", p3: "A. MATH-I (VR)", p4: "BEE(TS)", p5: "CS (B2)/CC (B1) PRACTICAL (Prac)", p6: "CS (B2)/CC (B1) PRACTICAL (Prac)", faculty: { 'E.CHEM(MJ)': 'DR. MEGHNA JUMBHLE', 'PSC(AS)': 'MR. AYAZ SHAIKH', 'A. MATH-I (VR)': 'MRS.VIDYA RAUT', 'BEE(TS)': 'MR. TUSHAR SHELKE', 'CS (B2)/CC (B1) PRACTICAL (Prac)': 'MRS. HITASHI CHAUHAN' } },
        { day: "THU", sec: "EE", room: "B-107", p1: "CS (B2)/CC (B1) PRACTICAL (Prac)", p2: "CS (B2)/CC (B1) PRACTICAL (Prac)", p3: "A.MATH-I(PD)", p4: "CS(MS)", p5: "T&P SESSION (Other)", p6: "T&P SESSION (Other)", faculty: { 'CS (B2)/CC (B1) PRACTICAL (Prac)': 'DR. MOHAMMAD SABIR', 'A.MATH-I(PD)': 'MR. PRASHANT DANGE', 'CS(MS)': 'DR. MOHAMMAD SABIR', 'T&P SESSION (Other)': 'N/A' } },
        { day: "THU", sec: "ME", room: "B-206", p1: "A.MATH-I(PD)", p2: "A.PHY(DM)", p3: "EG(SK)", p4: "CP (AS)", p5: "A.PHY(B2)/CC (B1) PRACTICAL (Prac)", p6: "A.PHY(B2)/CC (B1) PRACTICAL (Prac)", faculty: { 'A.MATH-I(PD)': 'MR. PRASHANT DANGE', 'A.PHY(DM)': 'MR. DHIRAJ MEGHE', 'EG(SK)': 'MR. SAMRAT KAVISHWAR', 'CP (AS)': 'MISS. AYUSHI SHARMA', 'A.PHY(B2)/CC (B1) PRACTICAL (Prac)': 'MR. DHIRAJ MEGHE' } },
        { day: "THU", sec: "CE", room: "B-207", p1: "FOV(AK)", p2: "A. MATH-I (VR)", p3: "A.PHY(DM)", p4: "EG(GK)", p5: "WS(B2)/ FOV(B1) PRACTICAL (Prac)", p6: "WS(B2)/ FOV(B1) PRACTICAL (Prac)", faculty: { 'FOV(AK)': 'MR. AMIT KHARWADE', 'A. MATH-I (VR)': 'MRS.VIDYA RAUT', 'A.PHY(DM)': 'MR. DHIRAJ MEGHE', 'EG(GK)': 'MR. GIRISAN KHAN', 'WS(B2)/ FOV(B1) PRACTICAL (Prac)': 'MR. AMIT KHARWADE' } },

        // FRI
        { day: "FRI", sec: "ACSE", room: "B-104", p1: "PSC(AS)", p2: "E.CHEM(SK)", p3: "A. MATH-I (MD)", p4: "BEE (RD)", p5: "CS (B1)/CC (B2) PRACTICAL (Prac)", p6: "CS (B1)/CC (B2) PRACTICAL (Prac)", faculty: { 'PSC(AS)': 'MR. AYAZ SHAIKH', 'E.CHEM(SK)': 'DR. SONIKA KOCHHAR', 'A. MATH-I (MD)': 'MRS. MONA DANGE', 'BEE (RD)': 'MRS. RACHANA DAGA', 'CS (B1)/CC (B2) PRACTICAL (Prac)': 'MRS. HITASHI CHAUHAN' } },
        { day: "FRI", sec: "BCSE", room: "B-105", p1: "A. MATH-I (MD)", p2: "CS(HC)", p3: "BEE (RK)", p4: "E.CHEM(SK)", p5: "WT(B1,B2) PRACTICAL (Prac)", p6: "WT(B1,B2) PRACTICAL (Prac)", faculty: { 'A. MATH-I (MD)': 'MRS. MONA DANGE', 'CS(HC)': 'MRS. HITASHI CHAUHAN', 'BEE (RK)': 'MR. RAHUL KADAM', 'E.CHEM(SK)': 'DR. SONIKA KOCHHAR', 'WT(B1,B2) PRACTICAL (Prac)': 'PURNIMA BHUYAR' } },
        { day: "FRI", sec: "IT", room: "B-106", p1: "E.CHEM(B1)/ BEE(B1) PRACTICAL (Prac)", p2: "E.CHEM(B1)/ BEE(B1) PRACTICAL (Prac)", p3: "E.CHEM(MJ)", p4: "BEE (TS)", p5: "A. MATH-I (VR)", p6: "TGM.LIBRARY (Library)", faculty: { 'E.CHEM(B1)/ BEE(B1) PRACTICAL (Prac)': 'DR. MEGHNA JUMBHLE', 'E.CHEM(MJ)': 'DR. MEGHNA JUMBHLE', 'BEE (TS)': 'MR. TUSHAR SHELKE', 'A. MATH-I (VR)': 'MRS.VIDYA RAUT', 'TGM.LIBRARY (Library)': 'N/A' } },
        { day: "FRI", sec: "EE", room: "B-107", p1: "A.MATH-I(PD)", p2: "BEE (RD)", p3: "A.PHY(JB)", p4: "EG(RD)", p5: "BEE(B1)/ SPI(B2) PRACTICAL (Prac)", p6: "BEE(B1)/ SPI(B2) PRACTICAL (Prac)", faculty: { 'A.MATH-I(PD)': 'MR. PRASHANT DANGE', 'BEE (RD)': 'MRS. RACHANA DAGA', 'A.PHY(JB)': 'DR. JITENDRA BHAISWAR', 'EG(RD)': 'MR. ROHAN DESHMUKH', 'BEE(B1)/ SPI(B2) PRACTICAL (Prac)': 'MRS. RACHANA DAGA' } },
        { day: "FRI", sec: "ME", room: "B-206", p1: "CP (B1,B2) PRACTICAL (Prac)", p2: "CP (B1,B2) PRACTICAL (Prac)", p3: "A.PHY(DM)", p4: "EG(SK)", p5: "T&P SESSION (Other)", p6: "T&P SESSION (Other)", faculty: { 'CP (B1,B2) PRACTICAL (Prac)': 'MISS. AYUSHI SHARMA', 'A.PHY(DM)': 'MR. DHIRAJ MEGHE', 'EG(SK)': 'MR. SAMRAT KAVISHWAR', 'T&P SESSION (Other)': 'N/A' } },
        { day: "FRI", sec: "CE", room: "B-207", p1: "EG(GK)", p2: "A.PHY(DM)", p3: "A. MATH-I (VR)", p4: "FOV(AK)", p5: "A.PHY(B1)/EG (B2) PRACTICAL (Prac)", p6: "A.PHY(B1)/EG (B2) PRACTICAL (Prac)", faculty: { 'EG(GK)': 'MR. GIRISAN KHAN', 'A.PHY(DM)': 'MR. DHIRAJ MEGHE', 'A. MATH-I (VR)': 'MRS.VIDYA RAUT', 'FOV(AK)': 'MR. AMIT KHARWADE', 'A.PHY(B1)/EG (B2) PRACTICAL (Prac)': 'MR. GIRISAN KHAN' } },

        // SAT
        { day: "SAT", sec: "ACSE", room: "B-104", p1: "E.CHEM(SK)", p2: "A. MATH-I (MD)", p3: "BEE (RD)", p4: "CS(HC)", p5: "CS (B2)/CC (B1) PRACTICAL (Prac)", p6: "CS (B2)/CC (B1) PRACTICAL (Prac)", faculty: { 'E.CHEM(SK)': 'DR. SONIKA KOCHHAR', 'A. MATH-I (MD)': 'MRS. MONA DANGE', 'BEE (RD)': 'MRS. RACHANA DAGA', 'CS(HC)': 'MRS. HITASHI CHAUHAN', 'CS (B2)/CC (B1) PRACTICAL (Prac)': 'MRS. HITASHI CHAUHAN' } },
        { day: "SAT", sec: "BCSE", room: "B-105", p1: "E.CHEM(B2)/ BEE(B1) PRACTICAL (Prac)", p2: "E.CHEM(B2)/ BEE(B1) PRACTICAL (Prac)", p3: "A. MATH-I (MD)", p4: "E.CHEM(SK)", p5: "T&P SESSION (Other)", p6: "T&P SESSION (Other)", faculty: { 'E.CHEM(B2)/ BEE(B1) PRACTICAL (Prac)': 'DR. SONIKA KOCHHAR', 'A. MATH-I (MD)': 'MRS. MONA DANGE', 'E.CHEM(SK)': 'DR. SONIKA KOCHHAR', 'T&P SESSION (Other)': 'N/A' } },
        { day: "SAT", sec: "IT", room: "B-106", p1: "CS(HC)", p2: "A. MATH-I (VR)", p3: "E.CHEM(MJ)", p4: "PSC(AS)", p5: "WT(B1,B2) PRACTICAL (Prac)", p6: "WT(B1,B2) PRACTICAL (Prac)", faculty: { 'CS(HC)': 'MRS. HITASHI CHAUHAN', 'A. MATH-I (VR)': 'MRS.VIDYA RAUT', 'E.CHEM(MJ)': 'DR. MEGHNA JUMBHLE', 'PSC(AS)': 'MR. AYAZ SHAIKH', 'WT(B1,B2) PRACTICAL (Prac)': 'PURNIMA BHUYAR' } },
        { day: "SAT", sec: "EE", room: "B-107", p1: "BEE (RD)", p2: "A.MATH-I(PD)", p3: "A.PHY(JB)", p4: "EG(RD)", p5: "BEE(B2)/ SPI(B1) PRACTICAL (Prac)", p6: "BEE(B2)/ SPI(B1) PRACTICAL (Prac)", faculty: { 'BEE (RD)': 'MRS. RACHANA DAGA', 'A.MATH-I(PD)': 'MR. PRASHANT DANGE', 'A.PHY(JB)': 'DR. JITENDRA BHAISWAR', 'EG(RD)': 'MR. ROHAN DESHMUKH', 'BEE(B2)/ SPI(B1) PRACTICAL (Prac)': 'MRS. RACHANA DAGA' } },
        { day: "SAT", sec: "ME", room: "B-206", p1: "CS (B1,B2) PRACTICAL (Prac)", p2: "CS (B1,B2) PRACTICAL (Prac)", p3: "A.MATH-I(PD)", p4: "EG(SK)", p5: "CS(MS)", p6: "TGM.LIBRARY (Library)", faculty: { 'CS (B1,B2) PRACTICAL (Prac)': 'DR. MOHAMMAD SABIR', 'A.MATH-I(PD)': 'MR. PRASHANT DANGE', 'EG(SK)': 'MR. SAMRAT KAVISHWAR', 'CS(MS)': 'DR. MOHAMMAD SABIR', 'TGM.LIBRARY (Library)': 'N/A' } },
        { day: "SAT", sec: "CE", room: "B-207", p1: "A. MATH-I (VR)", p2: "A.PHY(DM)", p3: "CS(MS)", p4: "EG(GK)", p5: "A.PHY(B2)/EG (B1) PRACTICAL (Prac)", p6: "A.PHY(B2)/EG (B1) PRACTICAL (Prac)", faculty: { 'A. MATH-I (VR)': 'MRS.VIDYA RAUT', 'A.PHY(DM)': 'MR. DHIRAJ MEGHE', 'CS(MS)': 'DR. MOHAMMAD SABIR', 'EG(GK)': 'MR. GIRISAN KHAN', 'A.PHY(B2)/EG (B1) PRACTICAL (Prac)': 'MR. GIRISAN KHAN' } },
    ];

    // Helper function to determine the CSS class based on content type
    function getCellClass(content) {
        if (!content || content.includes('LUNCH') || content.includes('BREAK') || content.includes('RECESS')) {
            return 'break-cell';
        }
        if (content.includes('PRACTICAL') || content.includes('Prac')) {
            return 'practical';
        }
        if (content.includes('LIBRARY')) {
            return 'library';
        }
        if (content.includes('T&P SESSION')) {
             return 'bg-yellow-100 text-yellow-800 font-medium';
        }
        // Special case for when content is missing/undefined in student view
        if (content === 'N/A' || content === 'DATA MISSING') {
            return 'data-missing';
        }
        // Default for theory classes
        return 'class-cell';
    }

    // Time slots for the headers
    const TIME_SLOTS = [
        "9:30-10:30", "10:30-11:30", "11:30-12:00 (Break)", "12:00-1:00", "1:00-2:00", "2:00-2:30 (Lunch)", "2:30-3:30", "3:30-4:15"
    ];

    // --- STUDENT TIMETABLE LOGIC ---

    function renderStudentTimetable(department, button) {
        const outputDiv = document.getElementById('student-timetable-output');

        // Clear previous active state and set new active button
        document.querySelectorAll('.dept-btn').forEach(btn => {
            btn.classList.remove('ring-4', 'ring-offset-2', 'ring-indigo-400');
        });
        if (button) {
            button.classList.add('ring-4', 'ring-offset-2', 'ring-indigo-400');
        }

        const filteredData = TIMETABLE_DATA.filter(item => item.sec === department);

        if (filteredData.length === 0) {
            outputDiv.innerHTML = `<p class="text-center text-red-500 p-8">No timetable data found for ${department}.</p>`;
            return;
        }

        let tableHTML = `
            <h3 class="text-xl font-semibold text-center py-4 bg-gray-50 rounded-t-lg">Timetable for Department: ${department}</h3>
            <table class="timetable-table">
                <thead>
                    <tr>
                        <th class="sticky-col">DAY</th>
                        <th class="sticky-col">ROOM NO.</th>
                        ${TIME_SLOTS.map(slot => `<th>${slot}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>
        `;

        const days = ["MON", "TUE", "WED", "THU", "FRI", "SAT"];

        days.forEach(day => {
            const dayData = filteredData.find(item => item.day === day);
            
            // Safety check: if no data for the day, show error placeholder
            if (!dayData) {
                tableHTML += `<tr><td class="day-cell sticky-col">${day}</td><td class="data-missing sticky-col" colspan="9">No schedule data available.</td></tr>`;
                return;
            }

            // Function to safely get content and strip indicators
            const getContent = (periodKey) => {
                const content = dayData[periodKey];
                // CRITICAL FIX: Ensure content exists before calling string methods
                if (content && typeof content === 'string') {
                    // Remove the type indicators for a cleaner student view
                    return content.replace('(Prac)', '').replace('(Library)', '').replace('(Other)', '').trim();
                }
                return 'N/A'; // Fallback content
            };

            tableHTML += `
                <tr>
                    <td class="day-cell sticky-col">${day}</td>
                    <td class="room-cell sticky-col">${dayData.room}</td>
                    
                    <td class="${getCellClass(dayData.p1)}">${getContent('p1')}</td>
                    <td class="${getCellClass(dayData.p2)}">${getContent('p2')}</td>
                    <td class="break-cell">BREAK</td>
                    <td class="${getCellClass(dayData.p3)}">${getContent('p3')}</td>
                    <td class="${getCellClass(dayData.p4)}">${getContent('p4')}</td>
                    <td class="break-cell">LUNCH</td>
                    <td class="${getCellClass(dayData.p5)}">${getContent('p5')}</td>
                    <td class="${getCellClass(dayData.p6)}">${getContent('p6')}</td>
                </tr>
            `;
        });

        tableHTML += `</tbody></table>`;
        outputDiv.innerHTML = tableHTML;
    }


    // --- TEACHER TIMETABLE LOGIC (FROM PREVIOUS FIX) ---

    // Extract unique faculty names for the dropdown
    function getUniqueFaculty() {
        const facultySet = new Set();
        TIMETABLE_DATA.forEach(dayData => {
            Object.values(dayData.faculty).forEach(name => {
                if (name !== 'N/A') {
                    facultySet.add(name);
                }
            });
        });
        return Array.from(facultySet).sort();
    }

    function populateFacultyDropdown() {
        const select = document.getElementById('faculty-select');
        const uniqueFaculty = getUniqueFaculty();

        uniqueFaculty.forEach(name => {
            const option = document.createElement('option');
            option.value = name;
            option.textContent = name;
            select.appendChild(option);
        });
    }

    function renderTeacherTimetable(facultyName) {
        const outputDiv = document.getElementById('teacher-timetable-output');
        if (!facultyName) {
            outputDiv.innerHTML = `<p class="text-center text-gray-500 p-8 border-2 border-dashed rounded-lg">Please select a Faculty Member from the dropdown to see their weekly schedule.</p>`;
            return;
        }

        let tableHTML = `
            <h3 class="text-xl font-semibold text-center py-4 bg-gray-50 rounded-t-lg">Schedule for ${facultyName}</h3>
            <table class="timetable-table">
                <thead>
                    <tr>
                        <th class="sticky-col">DAY</th>
                        ${TIME_SLOTS.map(slot => `<th>${slot}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>
        `;

        const days = ["MON", "TUE", "WED", "THU", "FRI", "SAT"];

        days.forEach(day => {
            let rowContent = `<td class="day-cell sticky-col">${day}</td>`;
            const dayEntries = TIMETABLE_DATA.filter(item => item.day === day);

            // Mapping of period keys to their index in TIME_SLOTS (excluding breaks/lunch)
            const periodKeys = ["p1", "p2", "p3", "p4", "p5", "p6"];
            let periodIndex = 0;

            for (let i = 0; i < TIME_SLOTS.length; i++) {
                const slot = TIME_SLOTS[i];

                if (slot.includes('Break') || slot.includes('Lunch')) {
                    rowContent += `<td class="break-cell">${slot.replace(/\s*\(.*\)/, '')}</td>`;
                    continue;
                }

                const periodKey = periodKeys[periodIndex];
                let content = 'FREE';
                let cellClass = 'bg-gray-200 text-gray-500';

                // Check all entries for this day
                for (const entry of dayEntries) {
                    const classContent = entry[periodKey];
                    
                    // The core fix: Check if the faculty name is associated with the current class content
                    if (entry.faculty && Object.entries(entry.faculty).some(([clsKey, facName]) => 
                        facName === facultyName && clsKey === classContent
                    )) {
                        content = `${classContent.replace('(Prac)', '').replace('(Library)', '').replace('(Other)', '').trim()} (${entry.sec}, ${entry.room})`;
                        cellClass = getCellClass(classContent);
                        break;
                    }
                }

                rowContent += `<td class="${cellClass}">${content}</td>`;
                periodIndex++;
            }

            tableHTML += `<tr>${rowContent}</tr>`;
        });

        tableHTML += `</tbody></table>`;
        outputDiv.innerHTML = tableHTML;
    }


    // --- VIEW TOGGLE & INITIALIZATION ---

    const studentView = document.getElementById('student-view');
    const teacherView = document.getElementById('teacher-view');
    const studentBtn = document.getElementById('student-view-btn');
    const teacherBtn = document.getElementById('teacher-view-btn');

    function toggleView(view) {
        if (view === 'student') {
            studentView.classList.remove('hidden');
            teacherView.classList.add('hidden');
            studentBtn.classList.add('bg-indigo-600', 'text-white', 'hover:bg-indigo-700');
            studentBtn.classList.remove('bg-gray-200', 'text-gray-700', 'hover:bg-gray-300');
            teacherBtn.classList.remove('bg-indigo-600', 'text-white', 'hover:bg-indigo-700');
            teacherBtn.classList.add('bg-gray-200', 'text-gray-700', 'hover:bg-gray-300');
        } else {
            studentView.classList.add('hidden');
            teacherView.classList.remove('hidden');
            teacherBtn.classList.add('bg-indigo-600', 'text-white', 'hover:bg-indigo-700');
            teacherBtn.classList.remove('bg-gray-200', 'text-gray-700', 'hover:bg-gray-300');
            studentBtn.classList.remove('bg-indigo-600', 'text-white', 'hover:bg-indigo-700');
            studentBtn.classList.add('bg-gray-200', 'text-gray-700', 'hover:bg-gray-300');
        }
    }

    // Set up listeners and initial view
    document.addEventListener('DOMContentLoaded', () => {
        studentBtn.addEventListener('click', () => toggleView('student'));
        teacherBtn.addEventListener('click', () => toggleView('teacher'));

        populateFacultyDropdown();
        
        // Default to showing the Student View
        toggleView('student');
    });

</script>

</body>
</html>