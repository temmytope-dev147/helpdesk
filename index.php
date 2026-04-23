<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sterling Assurance - IT Help Desk</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- LOGIN PAGE -->
<div id="loginPage" class="login-container">
    <div class="login-box">
        <div class="logo-container">
            <img src="https://sterlingassure.com/assets/uploads/logo.jpg"
                 alt="Sterling Assurance" class="logo"
                 onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2260%22 height=%2260%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 fill=%22%231565c0%22/><text x=%2210%22 y=%2265%22 fill=%22white%22 font-size=%2250%22>SA</text></svg>'">
            <h1>Help Desk Portal</h1>
            <p>Sign in with your company email</p>
        </div>
        <div id="loginError" class="error-message hidden"></div>
        <form id="loginForm" autocomplete="on">
            <div class="form-group">
                <label for="loginEmail">Email Address</label>
                <input type="email" id="loginEmail"
                       placeholder="youremail@sterlingassure.com"
                       autocomplete="username" required>
            </div>
            <div class="form-group">
                <label for="loginPassword">Password</label>
                <input type="password" id="loginPassword"
                       placeholder="••••••••"
                       autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn-primary">Sign In</button>
        </form>
        <div class="help-text">
            <p>Need help? Contact IT Support</p>
            <p>Email: <a href="mailto:itsupport@sterlingassure.com">itsupport@sterlingassure.com</a></p>
            <p>Tel: +08150643531-2</p>
        </div>
    </div>
</div>

<!-- DASHBOARD -->
<div id="dashboardPage" class="dashboard">

    <header class="header">
        <div class="header-content">
            <div class="header-left">
                <img src="https://sterlingassure.com/assets/uploads/logo.jpg"
                     alt="Sterling Assurance" class="logo"
                     onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2245%22 height=%2245%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 fill=%22%231565c0%22/><text x=%2210%22 y=%2265%22 fill=%22white%22 font-size=%2250%22>SA</text></svg>'">
                <div class="header-title">
                    <h1>IT Help Desk</h1>
                    <p>Sterling Assurance Nigeria Limited</p>
                </div>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <p id="userName"></p>
                    <span id="userEmail"></span>
                </div>
                <a id="adminPanelLink" href="http://localhost/Adminpanel/"
                   style="display:none;padding:10px 16px;background:#1751c8;color:#fff;
                          border-radius:6px;font-size:13px;font-weight:600;
                          text-decoration:none;margin-right:8px">
                    ⚙ Admin Panel
                </a>
                <button class="btn-logout" onclick="logout()">Logout</button>
            </div>
        </div>
    </header>

    <nav class="nav-bar">
        <div class="nav-content">
            <button class="nav-btn active" onclick="showPage('dashboard')">Dashboard</button>
            <button class="nav-btn"        onclick="showPage('hardware')">Hardware Support</button>
            <button class="nav-btn"        onclick="showPage('software')">Software Support</button>
            <button class="nav-btn"        onclick="showPage('tickets')">My Tickets</button>
        </div>
    </nav>

    <main class="main-content">

        <!-- Dashboard -->
        <section id="dashboardSection" class="page-section active">
            <div class="page-header"><h2>Welcome to IT Help Desk</h2></div>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Open Tickets</h3>
                    <div class="stat-number" id="openCount">0</div>
                </div>
                <div class="stat-card stat-card-warning">
                    <h3>In Progress</h3>
                    <div class="stat-number" id="progressCount">0</div>
                </div>
                <div class="stat-card stat-card-success">
                    <h3>Resolved</h3>
                    <div class="stat-number" id="resolvedCount">0</div>
                </div>
            </div>
            <div class="content-grid">
                <div class="card">
                    <h3>Quick Actions</h3>
                    <div class="quick-action" onclick="showPage('hardware')">
                        <h4>Report Hardware Issue</h4>
                        <p>Computers, printers, phones, etc.</p>
                    </div>
                    <div class="quick-action" onclick="showPage('software')">
                        <h4>Report Software Issue</h4>
                        <p>Applications, email, network access</p>
                    </div>
                </div>
                <div class="card">
                    <h3>Recent Tickets</h3>
                    <div id="recentTickets"></div>
                </div>
            </div>
        </section>

        <!-- Hardware Support -->
        <section id="hardwareSection" class="page-section">
            <div class="page-header">
                <h2>Hardware Support</h2>
                <p>Submit a ticket for hardware-related issues</p>
            </div>
            <div id="hardwareSuccess" class="success-message hidden"></div>
            <div class="form-container">
                <form id="hardwareForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Branch Office *</label>
                            <select id="hardwareBranch" required>
                                <option value="">Select your branch</option>
                                <option>Head Office - Lagos</option>
                                <option>Abuja Branch</option>
                                <option>Port Harcourt Branch</option>
                                <option>Kaduna Branch</option>
                                <option>NIIP</option>
                                <option>Ado-ekiti Branch</option>
                                <option>Calabar Branch</option>
                                <option>Illorin Branch</option>
                                <option>Warri Branch</option>
                                <option>Retail Marketing</option>
                                <option>BOI</option>
                                <option>Kano Branch</option>
                                <option>Ibadan Branch</option>
                                <option>Marina Branch</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Priority *</label>
                            <select id="hardwarePriority" required>
                                <option value="low">Low — Not urgent</option>
                                <option value="medium" selected>Medium — Normal priority</option>
                                <option value="high">High — Urgent</option>
                                <option value="critical">Critical — Business blocking</option>
                            </select>
                        </div>
                    </div>

                    <!-- Line of Business and Policy Number fields removed -->

                    <div class="form-group">
                        <label>Subject *</label>
                        <input type="text" id="hardwareSubject"
                               placeholder="Brief description of the hardware issue" required>
                    </div>
                    <div class="form-group">
                        <label>Description *</label>
                        <textarea id="hardwareDescription"
                                  placeholder="Provide detailed information..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Screenshot
                            <span style="font-weight:400;color:#999">(optional, max 5 MB)</span>
                        </label>
                        <label for="hardwareFile" class="upload-area">
                            <div class="upload-icon">📎</div>
                            <p>Click to upload a screenshot</p>
                            <span>PNG, JPG, GIF, WEBP up to 5 MB</span>
                            <input type="file" id="hardwareFile"
                                   accept="image/png,image/jpeg,image/gif,image/webp"
                                   onchange="handleFileUpload(event,'hardware')">
                        </label>
                        <div id="hardwareAttachments" class="attachments-list"></div>
                    </div>
                    <button type="submit" class="btn-submit">Submit Hardware Ticket</button>
                </form>
            </div>
        </section>

        <!-- Software Support -->
        <section id="softwareSection" class="page-section">
            <div class="page-header">
                <h2>Software Support</h2>
                <p>Submit a ticket for software-related issues</p>
            </div>
            <div id="softwareSuccess" class="success-message hidden"></div>
            <div class="form-container">
                <form id="softwareForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Branch Office *</label>
                            <select id="softwareBranch" required>
                                <option value="">Select your branch</option>
                                <option>Head Office - Lagos</option>
                                <option>Abuja Branch</option>
                                <option>Port Harcourt Branch</option>
                                <option>Kaduna Branch</option>
                                <option>NIIP</option>
                                <option>Ado-ekiti Branch</option>
                                <option>Calabar Branch</option>
                                <option>Illorin Branch</option>
                                <option>Warri Branch</option>
                                <option>Retail Marketing</option>
                                <option>BOI</option>
                                <option>Kano Branch</option>
                                <option>Ibadan Branch</option>
                                <option>Marina Branch</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Priority *</label>
                            <select id="softwarePriority" required>
                                <option value="low">Low — Not urgent</option>
                                <option value="medium" selected>Medium — Normal priority</option>
                                <option value="high">High — Urgent</option>
                                <option value="critical">Critical — Business blocking</option>
                            </select>
                        </div>
                    </div>

                    <!-- Line of Business and Policy Number fields removed -->

                    <div class="form-group">
                        <label>Subject *</label>
                        <input type="text" id="softwareSubject"
                               placeholder="Brief description of the software issue" required>
                    </div>
                    <div class="form-group">
                        <label>Description *</label>
                        <textarea id="softwareDescription"
                                  placeholder="Describe the software issue..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Screenshot
                            <span style="font-weight:400;color:#999">(optional, max 5 MB)</span>
                        </label>
                        <label for="softwareFile" class="upload-area">
                            <div class="upload-icon">📎</div>
                            <p>Click to upload a screenshot</p>
                            <span>PNG, JPG, GIF, WEBP up to 5 MB</span>
                            <input type="file" id="softwareFile"
                                   accept="image/png,image/jpeg,image/gif,image/webp"
                                   onchange="handleFileUpload(event,'software')">
                        </label>
                        <div id="softwareAttachments" class="attachments-list"></div>
                    </div>
                    <button type="submit" class="btn-submit">Submit Software Ticket</button>
                </form>
            </div>
        </section>

        <!-- My Tickets -->
        <section id="ticketsSection" class="page-section">
            <div class="page-header"><h2>My Tickets</h2></div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Ticket ID</th><th>Type</th><th>Subject</th>
                            <th>Line of Business</th><th>Policy No.</th>
                            <th>Branch</th><th>Priority</th><th>Status</th><th>Date</th>
                        </tr>
                    </thead>
                    <tbody id="ticketsTableBody">
                        <tr><td colspan="9" class="empty-table-message">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <footer class="footer">
        <p>© 2026 Sterling Assurance Nigeria Limited. All Rights Reserved.</p>
        <p>Sterling House, 284 Ikorodu Road Anthony, Lagos, Nigeria</p>
        <p>IT Support:
            <a href="mailto:itsupport@sterlingassure.com">itsupport@sterlingassure.com</a>
            | Tel: +0800Sterling 
        </p>
    </footer>
</div>

<script src="script.js"></script>
</body>
</html>