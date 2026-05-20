<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>SkillTree TVET — Next‑Gen Learning Platform</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: radial-gradient(circle at 10% 30%, #eef5fa, #e0eef7);
            font-family: 'Inter', sans-serif;
            color: #14222e;
            line-height: 1.5;
        }

        /* ----- GLASS NAVBAR ----- */
        .navbar {
            background: rgba(12, 35, 50, 0.85);
            backdrop-filter: blur(12px);
            padding: 0.9rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid rgba(255,255,240,0.2);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .logo i {
            font-size: 2rem;
            color: #ffda7c;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }
        .logo span {
            font-size: 1.6rem;
            font-weight: 800;
            background: linear-gradient(135deg, #fff, #ffda7c);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        .nav-links a {
            color: #f0f6fe;
            text-decoration: none;
            font-weight: 500;
            transition: 0.2s;
            font-size: 0.95rem;
        }
        .nav-links a:hover {
            color: #ffda7c;
            transform: translateY(-2px);
        }
        .user-avatar {
            background: #ffda7c;
            border-radius: 40px;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1e3a4d;
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            cursor: pointer;
        }

        /* container */
        .dashboard-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1.8rem;
        }

        /* trade modal (first visit) */
        .trade-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }
        .trade-card {
            background: rgba(255,255,255,0.98);
            max-width: 750px;
            width: 90%;
            border-radius: 2rem;
            padding: 2rem;
            box-shadow: 0 30px 40px rgba(0,0,0,0.2);
            text-align: center;
        }
        .trade-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px,1fr));
            gap: 1.2rem;
            margin: 1.8rem 0;
        }
        .trade-option {
            background: #f3f9ff;
            padding: 1rem;
            border-radius: 1.5rem;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
            font-weight: 500;
        }
        .trade-option:hover {
            background: #e2effa;
            transform: translateY(-4px);
        }
        .trade-option.selected {
            border-color: #2e7a9e;
            background: #e1f0fa;
            box-shadow: 0 8px 16px rgba(0,0,0,0.05);
        }

        /* stats row */
        .stats-row {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }
        .stat-glass {
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(8px);
            border-radius: 1.5rem;
            padding: 1rem 1.5rem;
            flex: 1;
            min-width: 170px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.03);
            border: 1px solid rgba(255,255,240,0.6);
        }
        .stat-value {
            font-size: 2.2rem;
            font-weight: 800;
            color: #1f5e7e;
        }

        /* module cards */
        .module-card {
            background: white;
            border-radius: 2rem;
            margin-bottom: 2rem;
            padding: 1.8rem;
            transition: 0.25s ease;
            box-shadow: 0 12px 24px rgba(0,0,0,0.05);
            border: 1px solid #eef2f8;
        }
        .module-header {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 1rem;
        }
        .lock-badge {
            background: #eef2fa;
            padding: 0.3rem 1rem;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .progress-bar-bg {
            background: #e2e8f0;
            border-radius: 30px;
            height: 10px;
            flex: 1;
            margin: 0 1rem;
        }
        .progress-fill {
            background: linear-gradient(90deg, #2c7da0, #60b8d4);
            border-radius: 30px;
            height: 100%;
            width: 0%;
        }
        .topic-chip {
            background: #f4f9fe;
            border-radius: 40px;
            padding: 0.4rem 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.8rem;
            margin: 0.3rem 0.3rem;
            cursor: pointer;
        }
        .resource-library, .internship-board, .community-section {
            background: rgba(255,255,255,0.75);
            backdrop-filter: blur(4px);
            border-radius: 2rem;
            padding: 1.5rem;
            margin-top: 2rem;
            border: 1px solid #fff9ef;
        }
        .btn-primary {
            background: #1e6a8c;
            border: none;
            border-radius: 40px;
            padding: 0.6rem 1.3rem;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-primary:hover {
            background: #0f5472;
            transform: scale(1.02);
        }
        .ai-tutor {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: radial-gradient(circle at 30% 20%, #1d5d7a, #0a3a4f);
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 15px 30px rgba(0,0,0,0.3);
            z-index: 220;
            transition: 0.2s;
        }
        .chat-window {
            position: fixed;
            bottom: 90px;
            right: 20px;
            width: 340px;
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 20px 35px rgba(0,0,0,0.2);
            display: none;
            flex-direction: column;
            z-index: 230;
            overflow: hidden;
        }
        .violation-toast {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: #e55353;
            color: white;
            padding: 0.5rem 1.2rem;
            border-radius: 40px;
            font-size: 0.8rem;
            z-index: 300;
            display: none;
            backdrop-filter: blur(4px);
        }

        @media (max-width: 750px) {
            .dashboard-container {
                padding: 0 1rem;
            }
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="logo"><i class="fas fa-seedling"></i><span>SkillTree TVET</span></div>
    <div class="nav-links">
        <a href="#">🏠 Home</a>
        <a href="#">📚 My Tracks</a>
        <a href="#">📄 Resources</a>
        <a href="#">💼 Internships</a>
        <a href="#">💬 Community</a>
        <div class="user-avatar"><i class="fas fa-user-astronaut"></i></div>
    </div>
</div>

<div class="dashboard-container" id="mainApp"></div>

<div class="ai-tutor" id="aiTutorBtn">
    <i class="fas fa-robot" style="font-size: 2rem;"></i>
</div>
<div class="chat-window" id="aiChatWindow">
    <div style="background:#1c5d79; color:white; padding:1rem; display:flex; justify-content:space-between;">
        <span><i class="fas fa-brain"></i> AI Learning Coach</span>
        <span id="closeChatBtn" style="cursor:pointer;">✕</span>
    </div>
    <div id="chatMessages" style="height:260px; overflow-y:auto; padding:1rem; background:#fefefe;"></div>
    <div style="padding:0.8rem; border-top:1px solid #ddd; display:flex;">
        <input type="text" id="chatInput" placeholder="Ask anything..." style="flex:1; border-radius:30px; border:1px solid #ccc; padding:0.5rem 1rem;">
        <button id="sendChatMsg" style="background:#1e6a8c; border:none; border-radius:30px; margin-left:0.5rem; padding:0.5rem 1rem; color:white;">Send</button>
    </div>
</div>

<div class="violation-toast" id="violationToast">⚠️ Tab switching detected!</div>

<script>
    // ------------------- MOCK DATA & STATE -------------------
    let currentTrade = localStorage.getItem("studentTrade");
    let enrolledModules = [];
    let violationsCount = 0;
    let quizWindowRef = null;
    let activeQuizTopic = null;

    // sectors & trades definition
    const sectorTree = {
        "Information Technology": ["Software Engineering", "Database Management", "Network Support"],
        "Hospitality & Tourism": ["Catering", "Front Office Management"],
        "Construction & Building": ["Bricklaying", "Plumbing & Pipefitting"]
    };
    // module catalog per trade (simplified)
    const modulesDB = {
        "Software Engineering": [
            { id: 101, name: "Programming Fundamentals", code: "SE101", progress: 0, locked: false, completionRequired: 70, topics: [
                { name: "Variables & Data Types", type: "notes", read: false, quizPassed: false },
                { name: "Control Flow (if/else)", type: "video", watched: false, quizPassed: false },
                { name: "Functions & Scope", type: "notes", read: false, quizPassed: false }
            ], loAssessmentLocked: true },
            { id: 102, name: "Web Development Basics", code: "SE102", progress: 0, locked: true, completionRequired: 70, topics: [] },
            { id: 103, name: "Database Integration", code: "SE103", progress: 0, locked: true, completionRequired: 70, topics: [] }
        ],
        "Database Management": [
            { id: 201, name: "SQL Fundamentals", code: "DB201", progress: 0, locked: false, topics: [], loAssessmentLocked: true }
        ],
        "Network Support": [
            { id: 301, name: "Network Basics", code: "NT301", progress: 0, locked: false, topics: [] }
        ]
    };

    // helper: get trade modules
    function getModulesForTrade(trade) {
        return modulesDB[trade] || [];
    }

    // progress calculation for a module (based on topic quiz completion)
    function updateModuleProgress(module) {
        if (!module.topics.length) return;
        let totalQuizzes = module.topics.length;
        let passedCount = module.topics.filter(t => t.quizPassed).length;
        module.progress = Math.floor((passedCount / totalQuizzes) * 100);
        // unlock LO assessment if all quizzes passed
        if (passedCount === totalQuizzes && totalQuizzes>0) module.loAssessmentLocked = false;
        // unlock next module if progress >= completionRequired
        let modulesArr = getModulesForTrade(currentTrade);
        let idx = modulesArr.findIndex(m => m.id === module.id);
        if (idx !== -1 && idx+1 < modulesArr.length && module.progress >= module.completionRequired) {
            modulesArr[idx+1].locked = false;
        }
        localStorage.setItem("modules_" + currentTrade, JSON.stringify(modulesArr));
    }

    // load trade modules from localStorage or default
    function loadTradeModules(trade) {
        let stored = localStorage.getItem("modules_" + trade);
        if (stored) return JSON.parse(stored);
        else return getModulesForTrade(trade);
    }

    // ----- RENDER MAIN DASHBOARD -----
    function renderDashboard() {
        if (!currentTrade) {
            showTradeSelectionModal();
            return;
        }
        let tradeModules = loadTradeModules(currentTrade);
        let totalProgress = tradeModules.length ? Math.floor(tradeModules.reduce((sum,m)=>sum+m.progress,0)/tradeModules.length) : 0;
        let completedModules = tradeModules.filter(m => m.progress >= 70).length;

        let html = `
            <div class="stats-row">
                <div class="stat-glass"><div class="stat-value">${tradeModules.length}</div><div>Modules</div></div>
                <div class="stat-glass"><div class="stat-value">${totalProgress}%</div><div>Overall Progress</div></div>
                <div class="stat-glass"><div class="stat-value">${completedModules}</div><div>Completed</div></div>
                <div class="stat-glass"><div class="stat-value">🏅 ${Math.floor(Math.random()*20)+5}</div><div>Skill Points</div></div>
            </div>
            <div id="modulesContainer"></div>
            <!-- Resource Library + Internships + Community -->
            <div class="resource-library">
                <i class="fas fa-archive"></i> <strong>Resource Hub</strong>
                <div style="display:flex; flex-wrap:wrap; gap:1rem; margin-top:1rem;">
                    <div class="topic-chip"><i class="fas fa-file-pdf"></i> National Past Papers</div>
                    <div class="topic-chip"><i class="fas fa-file-alt"></i> District Mocks</div>
                    <div class="topic-chip"><i class="fas fa-database"></i> Review Q-Bank</div>
                    <div class="topic-chip"><i class="fas fa-check-double"></i> Marking Guides</div>
                </div>
            </div>
            <div class="internship-board" id="internshipBoard"></div>
            <div class="community-section">
                <i class="fas fa-comments"></i> <strong>Student Suggestions & Peer Help</strong>
                <div id="communityFeed" style="margin-top:1rem; max-height:200px; overflow-y:auto;">
                    <div class="topic-chip">💡 <strong>Alice:</strong> Anyone has a summary for normalization?</div>
                    <div class="topic-chip">💬 <strong>John:</strong> I shared internship link at MTN!</div>
                </div>
                <input type="text" id="suggestionInput" placeholder="Share a tip or ask for help..." style="width:100%; margin-top:1rem; padding:0.6rem; border-radius:40px; border:1px solid #ccc;">
                <button id="postSuggestion" class="btn-primary" style="margin-top:0.5rem;">Post</button>
            </div>
        `;
        document.getElementById("mainApp").innerHTML = html;
        renderModulesList(tradeModules);
        renderInternships();
        attachCommunityEvents();
    }

    function renderModulesList(modules) {
        let container = document.getElementById("modulesContainer");
        container.innerHTML = "";
        modules.forEach(mod => {
            let lockIcon = mod.locked ? '<i class="fas fa-lock"></i> Locked' : '<i class="fas fa-unlock-alt"></i> Available';
            let card = document.createElement("div");
            card.className = "module-card";
            card.innerHTML = `
                <div class="module-header">
                    <div><h3>${mod.name}</h3><div style="font-size:0.8rem;">${mod.code}</div></div>
                    <div class="lock-badge">${lockIcon}</div>
                </div>
                <div style="display:flex; align-items:center; gap:0.8rem; margin:1rem 0;">
                    <span>Progress</span>
                    <div class="progress-bar-bg"><div class="progress-fill" style="width:${mod.progress}%;"></div></div>
                    <span>${mod.progress}%</span>
                </div>
                <div id="module-detail-${mod.id}"></div>
                <button class="viewModuleBtn" data-module-id="${mod.id}" style="background:#eef2fa; border:none; border-radius:2rem; padding:0.4rem 1rem;">📖 Explore Topics</button>
            `;
            container.appendChild(card);
            card.querySelector(".viewModuleBtn").onclick = () => showModuleDetails(mod);
            if(!mod.locked) showModuleDetails(mod); // auto expand first unlocked? optional
        });
    }

    function showModuleDetails(module) {
        let detailDiv = document.getElementById(`module-detail-${module.id}`);
        if(!detailDiv) return;
        if(!module.topics || !module.topics.length) {
            detailDiv.innerHTML = `<div class="topic-chip">✨ No topics loaded yet (demo)</div>`;
            return;
        }
        let topicsHtml = `<div style="margin-top:1rem;"><strong>🎯 Learning resources (must complete 90% before quizzes)</strong><ul style="list-style:none; margin-top:0.5rem;">`;
        module.topics.forEach((topic, idx) => {
            let statusIcon = topic.quizPassed ? '✅' : (topic.read || topic.watched ? '🟡' : '⚪');
            topicsHtml += `<li style="margin-bottom:0.7rem;">${statusIcon} <strong>${topic.name}</strong> (${topic.type})
                <button class="markResourceBtn" data-mid="${module.id}" data-tidx="${idx}" style="margin-left:1rem; background:#ddd; border:none; border-radius:30px; padding:0.2rem 0.8rem;">Mark as Read/Watched</button>
                <button class="quizBtn" data-mid="${module.id}" data-tidx="${idx}" style="margin-left:0.5rem; background:#2c7da0; color:white; border:none; border-radius:30px; padding:0.2rem 0.8rem;">Take Quiz</button>
            </li>`;
        });
        topicsHtml += `</ul>`;
        let loBtn = `<div style="margin-top:1rem;"><button class="loAssessmentBtn" data-mid="${module.id}" ${module.loAssessmentLocked ? 'disabled' : ''} style="background:#ffda7c; border:none; border-radius:2rem; padding:0.5rem 1rem;">📝 End-of-LO Assessment (${module.loAssessmentLocked ? 'Locked' : 'Ready'})</button></div>`;
        detailDiv.innerHTML = topicsHtml + loBtn;

        // attach resource marking
        document.querySelectorAll(`.markResourceBtn[data-mid="${module.id}"]`).forEach(btn => {
            btn.onclick = (e) => {
                let tidx = parseInt(btn.dataset.tidx);
                let topic = module.topics[tidx];
                if(topic.type === 'notes') topic.read = true;
                if(topic.type === 'video') topic.watched = true;
                let requiredCount = module.topics.length;
                let completedCount = module.topics.filter(t => (t.type==='notes' && t.read) || (t.type==='video' && t.watched)).length;
                let percent = (completedCount/requiredCount)*100;
                if(percent >= 90) alert(`✅ Great! You've completed ${Math.floor(percent)}% of required resources. You can now take topic quizzes.`);
                else alert(`📘 Marked "${topic.name}" as completed. Keep going!`);
                showModuleDetails(module); // refresh
                updateModuleProgress(module);
                renderModulesList(loadTradeModules(currentTrade)); // refresh progress bars
            };
        });
        // quiz triggers
        document.querySelectorAll(`.quizBtn[data-mid="${module.id}"]`).forEach(btn => {
            btn.onclick = () => {
                let tidx = parseInt(btn.dataset.tidx);
                let requiredCount = module.topics.length;
                let completedCount = module.topics.filter(t => (t.type==='notes' && t.read) || (t.type==='video' && t.watched)).length;
                let percent = (completedCount/requiredCount)*100;
                if(percent < 90) {
                    alert("❌ You must complete at least 90% of the learning resources before taking quizzes.");
                    return;
                }
                openSecureQuiz(module, tidx);
            };
        });
        let loBtnElem = detailDiv.querySelector('.loAssessmentBtn');
        if(loBtnElem) loBtnElem.onclick = () => openLOAssessment(module);
    }

    // ----- ANTI-CHEAT QUIZ WINDOW (tab switch, copy, auto-submit after 5 violations) -----
    let violationCounter = 0;
    function openSecureQuiz(module, topicIndex) {
        if (quizWindowRef && !quizWindowRef.closed) {
            quizWindowRef.focus();
            return;
        }
        violationCounter = 0;
        let topic = module.topics[topicIndex];
        quizWindowRef = window.open('', 'quizWindow', 'width=700,height=550,left=200,top=100');
        quizWindowRef.document.write(`
            <!DOCTYPE html>
            <html>
            <head><title>Quiz: ${topic.name}</title><style>
                body { font-family: 'Inter', sans-serif; padding: 2rem; background:#f4fafd; }
                .violation-alert { color:#d9534f; margin-top:1rem; font-weight:500; }
                button { background:#2c7da0; border:none; border-radius:30px; padding:0.6rem 1.5rem; color:white; margin-top:1rem; cursor:pointer;}
            </style></head>
            <body>
            <h2>📝 ${topic.name} Quiz</h2>
            <p><strong>Question:</strong> What is the main purpose of ${topic.name} in real-world software?</p>
            <div><input type="radio" name="quizAns" value="A"> A) To organize and reuse logic</div>
            <div><input type="radio" name="quizAns" value="B"> B) To increase file size</div>
            <div><input type="radio" name="quizAns" value="C"> C) Only for decoration</div>
            <button id="submitQuizBtn">Submit Answer</button>
            <div id="violationMsg" class="violation-alert"></div>
            <script>
                let vioCount = 0;
                const interval = setInterval(() => {
                    if(!document.hasFocus()) {
                        vioCount++;
                        document.getElementById('violationMsg').innerHTML = "⚠️ Tab switching detected ("+vioCount+"/5). After 5 violations quiz will auto-submit.";
                        if(vioCount >= 5) {
                            clearInterval(interval);
                            document.getElementById('violationMsg').innerHTML = "Auto-submitting due to violations.";
                            setTimeout(() => { window.close(); }, 1500);
                        }
                    }
                }, 800);
                document.getElementById('submitQuizBtn').onclick = () => {
                    let selected = document.querySelector('input[name="quizAns"]:checked');
                    let isCorrect = (selected && selected.value === 'A');
                    if(isCorrect) {
                        alert("✅ Correct! Great understanding.");
                        window.opener.postMessage({type: 'quizPassed', moduleId: ${module.id}, topicIndex: ${topicIndex}}, '*');
                    } else {
                        alert("❌ Incorrect. Please review the topic.");
                    }
                    window.close();
                };
                // block copy
                document.addEventListener('copy', e => e.preventDefault());
            <\/script>
            </body>
            </html>
        `);
        quizWindowRef.document.close();
        quizWindowRef.onbeforeunload = () => { quizWindowRef = null; };
        window.addEventListener('message', (e) => {
            if(e.data.type === 'quizPassed') {
                let mod = loadTradeModules(currentTrade).find(m => m.id === e.data.moduleId);
                if(mod && mod.topics[e.data.topicIndex]) {
                    mod.topics[e.data.topicIndex].quizPassed = true;
                    updateModuleProgress(mod);
                    localStorage.setItem("modules_" + currentTrade, JSON.stringify(loadTradeModules(currentTrade)));
                    renderDashboard();
                }
            }
        }, { once: true });
    }

    function openLOAssessment(module) {
        alert("📋 LO Assessment (Section A,B,C) - 100 marks, 70% passing. Anti-cheat active.\n This would open a full exam window.");
        // In real implementation, open a full exam page with similar anti-cheat measures.
    }

    // ----- INTERNSHIP BOARD (apply simulation)-----
    function renderInternships() {
        let internships = [
            { company: "MTN Rwanda", role: "Software Dev Intern", deadline: "2025-08-15" },
            { company: "KLab Hub", role: "Data Analyst Intern", deadline: "2025-09-01" }
        ];
        let board = document.getElementById("internshipBoard");
        if(board) {
            board.innerHTML = `<i class="fas fa-briefcase"></i> <strong>🎓 Internship Opportunities</strong><div id="internList"></div>`;
            let listDiv = document.getElementById("internList");
            listDiv.innerHTML = internships.map(i => `<div style="background:#fff6e5; margin:0.7rem 0; padding:0.7rem 1rem; border-radius:1.5rem; display:flex; justify-content:space-between;"><span><strong>${i.company}</strong> - ${i.role}</span><button class="applyBtn" data-company="${i.company}" style="background:#2c7da0; border:none; border-radius:30px; padding:0.2rem 1rem; color:white;">Apply</button></div>`).join('');
            document.querySelectorAll('.applyBtn').forEach(btn => {
                btn.onclick = () => alert("Application sent! You will receive feedback within 5 days.");
            });
        }
    }

    function attachCommunityEvents() {
        let postBtn = document.getElementById("postSuggestion");
        if(postBtn) {
            postBtn.onclick = () => {
                let input = document.getElementById("suggestionInput");
                if(input.value.trim()) {
                    let feed = document.getElementById("communityFeed");
                    let newMsg = document.createElement("div");
                    newMsg.className = "topic-chip";
                    newMsg.innerHTML = `💬 <strong>You:</strong> ${input.value}`;
                    feed.prepend(newMsg);
                    input.value = "";
                }
            };
        }
    }

    // ----- TRADE SELECTION MODAL -----
    function showTradeSelectionModal() {
        let modalDiv = document.createElement("div");
        modalDiv.className = "trade-modal";
        modalDiv.id = "tradeModal";
        modalDiv.innerHTML = `
            <div class="trade-card">
                <i class="fas fa-map-signs" style="font-size:2rem; color:#1e6a8c;"></i>
                <h2>Choose Your Professional Path</h2>
                <p>Select the sector and trade that matches your TVET program.</p>
                <div id="sectorContainer" style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;"></div>
                <div id="tradeContainer" style="margin-top:1.5rem;"></div>
                <button id="confirmTrade" class="btn-primary" style="margin-top:1.5rem;">Start Learning</button>
            </div>
        `;
        document.body.appendChild(modalDiv);
        let sectorContainer = document.getElementById("sectorContainer");
        for (let sector in sectorTree) {
            let secBtn = document.createElement("div");
            secBtn.className = "trade-option";
            secBtn.innerText = sector;
            secBtn.onclick = () => {
                document.querySelectorAll("#sectorContainer .trade-option").forEach(s => s.classList.remove("selected"));
                secBtn.classList.add("selected");
                showTradesForSector(sector);
            };
            sectorContainer.appendChild(secBtn);
        }
        function showTradesForSector(sector) {
            let trades = sectorTree[sector];
            let tradeContainer = document.getElementById("tradeContainer");
            tradeContainer.innerHTML = `<h4>Select your trade in ${sector}:</h4><div id="tradeGrid" style="display:flex; gap:0.8rem; flex-wrap:wrap;"></div>`;
            let grid = document.getElementById("tradeGrid");
            trades.forEach(trade => {
                let tradeOpt = document.createElement("div");
                tradeOpt.className = "trade-option";
                tradeOpt.innerText = trade;
                tradeOpt.onclick = () => {
                    document.querySelectorAll("#tradeGrid .trade-option").forEach(t => t.classList.remove("selected"));
                    tradeOpt.classList.add("selected");
                    window.selectedTradeFinal = trade;
                };
                grid.appendChild(tradeOpt);
            });
        }
        document.getElementById("confirmTrade").onclick = () => {
            if(window.selectedTradeFinal) {
                localStorage.setItem("studentTrade", window.selectedTradeFinal);
                currentTrade = window.selectedTradeFinal;
                document.getElementById("tradeModal")?.remove();
                renderDashboard();
                initAntiCheatGlobal();
            } else alert("Please select a trade.");
        };
    }

    // ----- GLOBAL ANTI-CHEAT (dashboard)-----
    function initAntiCheatGlobal() {
        let warns = 0;
        document.addEventListener('visibilitychange', () => {
            if(document.hidden) {
                warns++;
                let toast = document.getElementById("violationToast");
                toast.style.display = "block";
                toast.innerText = `⚠️ Tab change detected (${warns}/5) – focus on learning!`;
                setTimeout(() => toast.style.display = "none", 3000);
                if(warns >= 5) alert("Excessive tab switching. Your assessment will be flagged.");
            }
        });
        document.addEventListener('copy', (e) => {
            e.preventDefault();
            alert("Copying is disabled during learning sessions.");
        });
    }

    // ----- AI TUTOR (integrated)-----
    function setupAITutor() {
        const chatWin = document.getElementById("aiChatWindow");
        const btn = document.getElementById("aiTutorBtn");
        const close = document.getElementById("closeChatBtn");
        const send = document.getElementById("sendChatMsg");
        const input = document.getElementById("chatInput");
        const msgDiv = document.getElementById("chatMessages");
        btn.onclick = () => { chatWin.style.display = "flex"; };
        close.onclick = () => { chatWin.style.display = "none"; };
        send.onclick = () => {
            let q = input.value.trim();
            if(!q) return;
            msgDiv.innerHTML += `<div style="background:#eef2fa; border-radius:1rem; padding:0.5rem; margin-bottom:0.5rem;"><strong>You:</strong> ${q}</div>`;
            let answer = `🤖 AI: Great question! "${q}" relates to real‑world like ${currentTrade}. Example: In ${currentTrade}, this concept helps you build reliable systems. Let me know if you want a simpler example.`;
            msgDiv.innerHTML += `<div style="background:#e0f0fa; border-radius:1rem; padding:0.5rem; margin-bottom:0.5rem;">${answer}</div>`;
            input.value = "";
            msgDiv.scrollTop = msgDiv.scrollHeight;
        };
        input.addEventListener("keypress", (e) => { if(e.key === "Enter") send.click(); });
    }

    // ----- initial load -----
    if(!localStorage.getItem("studentTrade")) {
        showTradeSelectionModal();
    } else {
        currentTrade = localStorage.getItem("studentTrade");
        renderDashboard();
        initAntiCheatGlobal();
    }
    setupAITutor();
</script>
</body>
</html>