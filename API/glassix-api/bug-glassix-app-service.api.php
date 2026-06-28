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
            --accent-hover: #4a7cdd;
            --success: #22c55e;
            --success-dim: rgba(34,197,94,.15);
            --warning: #f59e0b;
            --warning-dim: rgba(245,158,11,.15);
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
            transition: background .15s, border-color .15s, opacity .15s;
            width: 100%;
            justify-content: flex-start;
        }

        .btn-action i {
            font-size: 15px;
            flex-shrink: 0;
        }

        .btn-action .btn-label {
            flex: 1;
        }

        .btn-action .btn-sub {
            font-size: 11px;
            font-weight: 400;
            color: inherit;
            opacity: 0.65;
        }

        .btn-search {
            background: var(--accent-dim);
            color: var(--accent);
            border-color: rgba(91,141,238,.25);
        }
        .btn-search:hover { background: rgba(91,141,238,.25); border-color: var(--accent); }

        .btn-crm {
            background: var(--warning-dim);
            color: var(--warning);
            border-color: rgba(245,158,11,.25);
        }
        .btn-crm:hover { background: rgba(245,158,11,.25); border-color: var(--warning); }

        .card-section {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }

        .card-section-header {
            padding: 9px 14px;
            border-bottom: 1px solid var(--border);
            font-size: 11px;
            font-weight: 600;
            color: var(--text2);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .card-section-body {
            padding: 12px;
        }

        .btn-cancel {
            background: rgba(239,68,68,.12);
            color: var(--danger);
            border-color: rgba(239,68,68,.25);
        }
        .btn-cancel:hover { background: rgba(239,68,68,.22); border-color: var(--danger); }
    </style>
</head>
<body>
    <div class="app-header">
        <div class="logo-dot"></div>
        <div class="title">מוקד<span>נט</span> · מח' אינטרנט</div>
    </div>

    <div class="content">
        <div class="btn-group-row">
            <a id="searchLink" class="btn-action btn-search" href="javascript:void(0);" onclick="return openPopup('search')">
                <i class="bi bi-search"></i>
                <div class="btn-label">
                    חיפוש הזמנת אינטרנט
                    <div class="btn-sub">שלושה חודשים אחורה</div>
                </div>
            </a>
            <a id="crmLink" class="btn-action btn-crm" href="javascript:void(0);" onclick="return openPopup('crm')">
                <i class="bi bi-person-lines-fill"></i>
                <div class="btn-label">טען CRM</div>
            </a>
        </div>

        <div class="card-section">
            <div class="card-section-header">דיווח מח' אינטרנט</div>
            <div class="card-section-body">
                <button id="internetGroupbutton" class="btn-action btn-cancel">
                    <i class="bi bi-x-circle"></i>
                    <div class="btn-label">ביטול הזמנה</div>
                </button>
            </div>
        </div>
    </div>

    <script>
        var url = '';
        var crmurl = '';
        var customerFirstName = null;
        var updatedPhoneNumber = null;

        window.onTicketLoaded = function(ticket) {
            console.log(ticket);
            const firstType1Participant = ticket.participants.find(p => p.type === 1);
            const identifier = firstType1Participant ? firstType1Participant.identifier : null;
            const protocolType = firstType1Participant ? firstType1Participant.protocolType : null;
            const currentDate = new Date();
            const currentDateString = currentDate.toISOString().split('T')[0];
            const startDate = new Date();
            startDate.setMonth(currentDate.getMonth() - 3);
            const startDateString = startDate.toISOString().split('T')[0];
            customerFirstName = firstType1Participant.name.split(' ')[0];

            if (protocolType === "WhatsApp" || protocolType === "SMS") {
                updatedPhoneNumber = identifier.replace(/^972/, '0');
                url = `https://www.bug.co.il/management/order/search?filterByStartDate=${startDateString}&filterByEndDate=${currentDateString}&filterByCustomerPhone=${updatedPhoneNumber}`;
                crmurl = `https://alon.alexisdeveloping.com/includes/crm.inc.php?caller=${updatedPhoneNumber}&fname=${customerFirstName}`;
            } else if (protocolType === "Mail") {
                url = `https://www.bug.co.il/management/order/search?filterByStartDate=${startDateString}&filterByEndDate=${currentDateString}&filterByCustomerEmail=${identifier}`;
                crmurl = '';
                document.getElementById('crmLink').style.display = 'none';
            } else {
                const el = document.getElementById('searchLink');
                el.textContent = 'לא ניתן לבצע חיפוש עבור פרוטוקול זה';
                el.removeAttribute('href');
                el.removeAttribute('onclick');
                el.style.opacity = '0.5';
                el.style.cursor = 'default';
            }
        };

        document.getElementById('internetGroupbutton').addEventListener('click', function() {
            const subject = encodeURIComponent(updatedPhoneNumber + " " + customerFirstName + " - ביטול הזמנת אינטרנט");
            const mailtoLink = `mailto:web8@bug.co.il;web4@bug.co.il;web12@bug.co.il?cc=alex@bug.co.il&subject=${subject}`;
            const popup = window.open('', '_blank', 'width=100,height=100');
            if (popup) {
                popup.document.write(`<html><head><script>window.location.href="${mailtoLink}";setTimeout(()=>window.close(),500);<\/script></head><body></body></html>`);
            } else {
                alert("הדפדפן חסם את החלון הקופץ. יש לאפשר פתיחת חלונות קופצים.");
            }
        });

        function openPopup(type) {
            var width = 1700, height = 950;
            var left = (screen.width - width) / 2;
            var top = (screen.height - height) / 2;
            var popupUrl = type === 'search' ? url : (type === 'crm' ? crmurl : '');
            if (popupUrl) {
                var w = window.open(popupUrl, 'popupWindow', `width=${width},height=${height},top=${top},left=${left},resizable=yes,scrollbars=yes`);
                if (w) { w.focus(); return false; }
            }
        }
    </script>
</body>
</html>
