<?php
header('X-Frame-Options: ALLOWALL');
header("Content-Security-Policy: frame-ancestors *");
?>

<!DOCTYPE html>
<html dir="rtl" lang="he-IL">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="Content-Security-Policy" content="block-all-mixed-content" />
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <script src="https://cdn.glassix.com/clients/embedded-app-iframe.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Assistant:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>מוקד-נט</title>
    <style>
        :root {
            --bg: #0d0f16;
            --bg2: #13161f;
            --bg3: #1a1e2b;
            --bg4: #212638;
            --border: rgba(255,255,255,.07);
            --border2: rgba(255,255,255,.14);
            --text: #e2e5f0;
            --text2: #7c829c;
            --accent: #5b8dee;
            --accent-dim: rgba(91,141,238,.15);
            --warning: #f59e0b;
            --warning-dim: rgba(245,158,11,.15);
            --success: #22c55e;
            --success-dim: rgba(34,197,94,.15);
            --danger: #ef4444;
            --radius: 10px;
            --radius-sm: 6px;
            --font: 'Assistant', sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: var(--font);
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-size: 14px;
        }

        .app-header {
            background: var(--bg2);
            border-bottom: 1px solid var(--border);
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .app-header .logo-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent);
            flex-shrink: 0;
        }

        .app-header .title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text2);
            letter-spacing: 0.02em;
        }

        .app-header .title span {
            color: var(--accent);
        }

        .content {
            flex: 1;
            padding: 12px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .phone-badge {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: 0.04em;
        }

        .phone-badge i {
            color: var(--accent);
            font-size: 15px;
        }

        .greeting-box {
            background: var(--accent-dim);
            border: 1px solid rgba(91,141,238,.2);
            border-radius: var(--radius-sm);
            padding: 9px 12px;
            font-size: 13px;
            color: var(--accent);
            font-weight: 500;
            line-height: 1.5;
            min-height: 38px;
        }

        .btn-group-row {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .btn-action {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            font-family: var(--font);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            text-decoration: none;
            transition: background .15s, border-color .15s;
            width: 100%;
            justify-content: flex-start;
        }

        .btn-action i {
            font-size: 15px;
            flex-shrink: 0;
        }

        .btn-crm {
            background: var(--warning-dim);
            color: var(--warning);
            border-color: rgba(245,158,11,.25);
        }
        .btn-crm:hover { background: rgba(245,158,11,.25); border-color: var(--warning); }

        .btn-lab {
            background: var(--success-dim);
            color: var(--success);
            border-color: rgba(34,197,94,.25);
        }
        .btn-lab:hover { background: rgba(34,197,94,.25); border-color: var(--success); }
    </style>
</head>
<body>
    <div class="app-header">
        <div class="logo-dot"></div>
        <div class="title">מוקד<span>נט</span> · תמיכה</div>
    </div>

    <div class="content">
        <div id="phoneBadge" class="phone-badge" style="display:none;">
            <i class="bi bi-telephone-fill"></i>
            <span id="phoneNum"></span>
        </div>

        <div id="greetingBox" class="greeting-box" style="display:none;">
            <span id="greeting"></span>
        </div>

        <div class="btn-group-row">
            <a id="crmLink" class="btn-action btn-crm" href="javascript:void(0);" onclick="return openPopup('crm')">
                <i class="bi bi-person-lines-fill"></i>
                <span>טען CRM</span>
            </a>
            <button id="labButton" class="btn-action btn-lab">
                <i class="bi bi-tools"></i>
                <span>שלח למעבדה</span>
            </button>
        </div>
    </div>

    <script>
        var url = '';
        var crmurl = '';
        var customerFirstName = null;
        var updatedPhoneNumber = null;

        window.onTicketLoaded = function(ticket) {
            const firstType1Participant = ticket.participants.find(p => p.type === 1);
            const identifier = firstType1Participant ? firstType1Participant.identifier : null;
            const protocolType = firstType1Participant ? firstType1Participant.protocolType : null;
            const currentDate = new Date();
            const currentDateString = currentDate.toISOString().split('T')[0];
            const startDate = new Date();
            startDate.setMonth(currentDate.getMonth() - 3);
            const startDateString = startDate.toISOString().split('T')[0];
            customerFirstName = firstType1Participant.name.split(' ')[0];
            updatedPhoneNumber = identifier.replace(/^972/, '0');

            if (protocolType === "WhatsApp" || protocolType === "SMS") {
                document.getElementById('phoneNum').textContent = updatedPhoneNumber;
                document.getElementById('phoneBadge').style.display = 'flex';
                url = `https://www.bug.co.il/management/order/search?filterByStartDate=${startDateString}&filterByEndDate=${currentDateString}&filterByCustomerPhone=${updatedPhoneNumber}`;
                crmurl = `https://alon.alexisdeveloping.com/includes/crm.inc.php?caller=${updatedPhoneNumber}&fname=${customerFirstName}`;
            } else if (protocolType === "Mail") {
                url = `https://www.bug.co.il/management/order/search?filterByStartDate=${startDateString}&filterByEndDate=${currentDateString}&filterByCustomerEmail=${identifier}`;
                document.getElementById('crmLink').style.display = 'none';
            } else {
                document.getElementById('crmLink').style.display = 'none';
            }

            addGreeting();

            document.getElementById('labButton').addEventListener('click', function() {
                const subject = encodeURIComponent(updatedPhoneNumber + " " + customerFirstName);
                const mailtoLink = `mailto:chaim@modan.co.il;avinoam@modan.co.il;matan@bug.co.il?cc=eyal@bug.co.il;alonv@bug.co.il;bat-el@bug.co.il&subject=${subject}`;
                const popup = window.open('', '_blank', 'width=100,height=100');
                if (popup) {
                    popup.document.write(`<html><head><script>window.location.href="${mailtoLink}";setTimeout(()=>window.close(),500);<\/script></head><body></body></html>`);
                } else {
                    alert("הדפדפן חסם את החלון הקופץ. יש לאפשר פתיחת חלונות קופצים.");
                }
            });
        };

        function addGreeting() {
            const hours = new Date().getHours();
            let timeGreeting = hours < 12 ? 'בוקר טוב' : hours < 16 ? 'צהריים טובים' : hours < 19 ? 'אחר צהריים טובים' : 'ערב טוב';

            const greetings = [
                `שלום ${customerFirstName}, ${timeGreeting}! מצטערים על ההמתנה.`,
                `${timeGreeting}, ${customerFirstName} — מתנצלים על העיכוב.`,
                `היי ${customerFirstName}, ${timeGreeting}. סליחה על האיחור.`,
                `שלום ${customerFirstName}, ${timeGreeting}. סליחה על ההמתנה.`,
                `${customerFirstName} היי, ${timeGreeting}! מתנצלים שלקח זמן.`,
                `היי ${customerFirstName}! ${timeGreeting}, אנחנו מצטערים על העיכוב.`,
                `היי ${customerFirstName}! ${timeGreeting}, אני עכשיו רואה את פנייתך, סליחה על העיכוב.`
            ];

            const message = greetings[Math.floor(Math.random() * greetings.length)];
            document.getElementById('greeting').textContent = message;
            document.getElementById('greetingBox').style.display = 'block';
        }

        function openPopup(type) {
            var width = 1700, height = 900;
            var left = (screen.width - width) / 2;
            var top = (screen.height - height) / 2;
            var popupUrl = type === 'search' ? url : (type === 'crm' ? crmurl : '');
            if (popupUrl) {
                var w = window.open(popupUrl, 'popupWindow', `width=${width},height=${height},top=${top},left=${left},resizable=yes,scrollbars=yes`);
                if (w) { w.focus(); return false; }
            } else {
                alert('לא נמצא קישור לפתיחה');
            }
        }
    </script>
</body>
</html>
