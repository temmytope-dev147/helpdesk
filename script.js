/**
 * script.js — Sterling Assurance IT Help Desk
 */

// ── Global CSRF token (fetched once on page load) ─────────────────────────────
let _csrfToken = '';

async function initCsrfToken() {
    try {
        const res  = await fetch('api.php?action=csrf_token', { credentials: 'include' });
        const data = await res.json();
        _csrfToken = data.csrf_token || '';
    } catch (e) {
        console.error('Failed to fetch CSRF token', e);
    }
}

document.addEventListener("DOMContentLoaded", async function () {

    // ── Fetch CSRF token first before anything else ───────────────────────────
    await initCsrfToken();

    // ── Check if already logged in ────────────────────────────────────────────
    fetch("api.php?action=me", { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.success) showDashboard(data.user);
            else              showLoginPage();
        })
        .catch(() => showLoginPage());

    const loginForm = document.getElementById("loginForm");
    if (loginForm) {
        loginForm.addEventListener("submit", async function (e) {
            e.preventDefault();
            const email    = document.getElementById("loginEmail").value.trim();
            const password = document.getElementById("loginPassword").value;
            const errorEl  = document.getElementById("loginError");
            errorEl.classList.add("hidden");
            errorEl.innerText = "";

            // Always get a fresh CSRF token before login
            await initCsrfToken();

            fetch("api.php?action=login", {
                method:      "POST",
                credentials: "include",
                headers: {
                    "Content-Type":  "application/x-www-form-urlencoded",
                    "X-CSRF-Token":  _csrfToken
                },
                body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showDashboard(data.user);
                } else {
                    errorEl.innerText = data.message || "Login failed.";
                    errorEl.classList.remove("hidden");
                }
            })
            .catch(() => {
                errorEl.innerText = "Network error. Please try again.";
                errorEl.classList.remove("hidden");
            });
        });
    }

    const hardwareForm = document.getElementById("hardwareForm");
    if (hardwareForm) {
        hardwareForm.addEventListener("submit", function (e) {
            e.preventDefault();
            submitTicket("hardware");
        });
    }

    const softwareForm = document.getElementById("softwareForm");
    if (softwareForm) {
        softwareForm.addEventListener("submit", function (e) {
            e.preventDefault();
            submitTicket("software");
        });
    }
});

function showLoginPage() {
    document.getElementById("loginPage").style.display = "flex";
    document.getElementById("dashboardPage").classList.remove("active");
}

function showDashboard(user) {
    document.getElementById("loginPage").style.display = "none";
    document.getElementById("dashboardPage").classList.add("active");

    const nameEl  = document.getElementById("userName");
    const emailEl = document.getElementById("userEmail");
    if (nameEl)  nameEl.innerText  = user.fullname || user.email;
    if (emailEl) emailEl.innerText = user.email;

    const adminLink = document.getElementById("adminPanelLink");
    if (adminLink) adminLink.style.display = user.is_admin ? "inline-block" : "none";

    loadTickets();
    showPage("dashboard");

    if (window._ticketRefreshTimer) clearInterval(window._ticketRefreshTimer);
    window._ticketRefreshTimer = setInterval(loadTickets, 30000);
}

function showPage(page) {
    document.querySelectorAll(".nav-btn").forEach(b => b.classList.remove("active"));
    const activeBtn = document.querySelector(`.nav-btn[onclick*="'${page}'"]`);
    if (activeBtn) activeBtn.classList.add("active");

    document.querySelectorAll(".page-section").forEach(s => s.classList.remove("active"));
    const map = {
        dashboard: "dashboardSection",
        hardware:  "hardwareSection",
        software:  "softwareSection",
        tickets:   "ticketsSection"
    };
    if (map[page]) document.getElementById(map[page]).classList.add("active");
    if (page === "tickets") loadTickets();
}

function submitTicket(type) {
    const subject     = document.getElementById(type + "Subject").value.trim();
    const description = document.getElementById(type + "Description").value.trim();
    const priority    = document.getElementById(type + "Priority").value;
    const branch      = document.getElementById(type + "Branch").value;
    const successEl   = document.getElementById(type + "Success");
    const fileInput   = document.getElementById(type + "File");

    const selectedFile = (fileInput && fileInput.files.length > 0)
                         ? fileInput.files[0]
                         : null;

    successEl.classList.add("hidden");
    successEl.innerText = "";

    if (!branch) {
        showAlert(successEl, "Please select a branch.", false);
        return;
    }

    const formData = new FormData();
    formData.append("type",        type === "hardware" ? "Hardware" : "Software");
    formData.append("subject",     subject);
    formData.append("description", description);
    formData.append("priority",    priority);
    formData.append("branch",      branch);

    if (selectedFile) {
        formData.append("screenshot", selectedFile);
    }

    fetch("api.php?action=create_ticket", {
        method:      "POST",
        credentials: "include",
        headers:     { "X-CSRF-Token": _csrfToken },
        body:        formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert(successEl, "✅ Ticket submitted successfully!", true);
            document.getElementById(type + "Form").reset();
            document.getElementById(type + "Attachments").innerHTML = "";
            loadTickets();
        } else {
            showAlert(successEl, data.message || "Failed to submit ticket.", false);
        }
    })
    .catch(() => showAlert(successEl, "Network error. Please try again.", false));
}

function showAlert(el, message, success) {
    el.innerText        = message;
    el.style.background = success ? "#e8f5e9" : "#ffebee";
    el.style.color      = success ? "#2e7d32" : "#c62828";
    el.style.borderLeft = success ? "4px solid #2e7d32" : "4px solid #c62828";
    el.classList.remove("hidden");
    el.scrollIntoView({ behavior: "smooth", block: "nearest" });
}

function loadTickets() {
    fetch("api.php?action=fetch_tickets", { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const tickets = data.tickets || [];
            renderTicketsTable(tickets);
            renderRecentTickets(tickets);
            updateStats(tickets);
        })
        .catch(console.error);
}

function renderTicketsTable(tickets) {
    const tbody = document.getElementById("ticketsTableBody");
    if (!tbody) return;
    if (tickets.length === 0) {
        tbody.innerHTML = `<tr><td colspan="9" class="empty-table-message">No tickets found</td></tr>`;
        return;
    }
    tbody.innerHTML = tickets.map(t => `
        <tr>
            <td class="ticket-id">#${t.id}</td>
            <td>${escHtml(t.type)}</td>
            <td>${escHtml(t.subject)}</td>
            <td>${escHtml(t.lob || '—')}</td>
            <td>${escHtml(t.policy_no || '—')}</td>
            <td>${escHtml(t.branch)}</td>
            <td><span class="badge badge-priority-${(t.priority||'').toLowerCase()}">${escHtml(t.priority)}</span></td>
            <td><span class="badge ${statusBadgeClass(t.status)}">${escHtml(t.status)}</span></td>
            <td>${formatDate(t.created_at)}</td>
        </tr>
    `).join("");
}

function renderRecentTickets(tickets) {
    const container = document.getElementById("recentTickets");
    if (!container) return;
    const recent = tickets.slice(0, 5);
    if (recent.length === 0) {
        container.innerHTML = `<p style="color:#999;font-size:13px;">No recent tickets</p>`;
        return;
    }
    container.innerHTML = recent.map(t => `
        <div class="recent-ticket">
            <div class="recent-ticket-header">
                <h4>${escHtml(t.subject)}</h4>
                <span class="badge ${statusBadgeClass(t.status)}">${escHtml(t.status)}</span>
            </div>
            <p>${escHtml(t.type)} · ${escHtml(t.lob || '')}${t.lob ? ' · ' : ''}${escHtml(t.branch)} · ${formatDate(t.created_at)}</p>
        </div>
    `).join("");
}

function updateStats(tickets) {
    setEl("openCount",     tickets.filter(t => t.status === "Open").length);
    setEl("progressCount", tickets.filter(t => t.status === "In Progress").length);
    setEl("resolvedCount", tickets.filter(t => t.status === "Resolved").length);
}

// ── Logout — destroys server session then hard redirects ──────────────────────
function logout() {
    fetch("api.php?action=logout", {
        method:      "POST",
        credentials: "include",
        headers:     { "X-CSRF-Token": _csrfToken }
    })
    .finally(() => {
        _csrfToken = '';
        if (window._ticketRefreshTimer) {
            clearInterval(window._ticketRefreshTimer);
        }
        window.location.replace("index.php");
    });
}

function handleFileUpload(event, type) {
    const files  = Array.from(event.target.files);
    const listEl = document.getElementById(type + "Attachments");
    listEl.innerHTML = "";
    files.forEach(file => {
        const item = document.createElement("div");
        item.className = "attachment-item";
        item.innerHTML = `
            <div class="attachment-info">
                <span>🖼️</span>
                <strong>${escHtml(file.name)}</strong>
                <span>${formatBytes(file.size)}</span>
            </div>
            <button type="button" class="btn-remove" onclick="this.parentElement.remove()">×</button>
        `;
        listEl.appendChild(item);
    });
}

function escHtml(str) {
    if (str == null) return "";
    return String(str)
        .replace(/&/g,"&amp;").replace(/</g,"&lt;")
        .replace(/>/g,"&gt;").replace(/"/g,"&quot;");
}

function formatDate(str) {
    if (!str) return "—";
    const d = new Date(str);
    return isNaN(d) ? str : d.toLocaleDateString("en-NG",{day:"2-digit",month:"short",year:"numeric"});
}

function formatBytes(bytes) {
    if (bytes < 1024)    return bytes + " B";
    if (bytes < 1048576) return (bytes/1024).toFixed(1) + " KB";
    return (bytes/1048576).toFixed(1) + " MB";
}

function statusBadgeClass(status) {
    switch ((status||"").toLowerCase()) {
        case "open":        return "badge-open";
        case "in progress": return "badge-progress";
        case "resolved":    return "badge-resolved";
        default:            return "badge-open";
    }
}

function setEl(id, value) {
    const el = document.getElementById(id);
    if (el) el.innerText = value;
}