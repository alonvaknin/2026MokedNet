<?php
if (isset($_GET['depart']) && $_GET['depart'] == 1) {
    $depart = 1;
} else {
    $depart = 2;
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="he-IL">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="Content-Security-Policy" content="block-all-mixed-content" />
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <script src="https://cdn.glassix.com/clients/embedded-app-iframe.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <title></title>
</head>
<style>
    #searchLink {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 15px;
        font-size: 16px;
        color: #ffffff;
        background-color: #007bff;
        text-decoration: none;
        border-radius: 8px;
        transition: background 0.3s ease-in-out;
    }

    #searchLink:hover {
        background-color: #0056b3;
    }

    #searchLink i {
        font-size: 18px;
    }

    span {
        font-size: 14px;
        color: #d1ecff;
    }
</style>

<body>
    <?php
    switch ($depart) {
        case '1':
            echo    '<a id="searchLink" href="#" onclick="return openPopup()">
                    <i class="fas fa-search"></i> חיפוש הזמנת אינטרנט <span>שלושה חודשים אחורה</span>
                    </a>
            ';
            break;

        default:
            # code...
            break;
    }
    ?>
    <script>
        var url = '';
        window.onTicketLoaded = function(ticket) {
            const firstType1Participant = ticket.participants.find(participant => participant.type === 1);
            const identifier = firstType1Participant ? firstType1Participant.identifier : null;
            const protocolType = firstType1Participant ? firstType1Participant.protocolType : null;
            const currentDate = new Date();
            const currentDateString = currentDate.toISOString().split('T')[0];
            const startDate = new Date();
            startDate.setMonth(currentDate.getMonth() - 3);
            const startDateString = startDate.toISOString().split('T')[0];
            if (protocolType === "WhatsApp" || protocolType === "SMS") {
                let updatedPhoneNumber = identifier.replace(/^972/, '0');
                url = `https://www.bug.co.il/management/order/search?filterByStartDate=${startDateString}&filterByEndDate=${currentDateString}&filterByCustomerPhone=${updatedPhoneNumber}`;
            } else if (protocolType === "Mail") {
                url = `https://www.bug.co.il/management/order/search?filterByStartDate=${startDateString}&filterByEndDate=${currentDateString}&filterByCustomerEmail=${identifier}`;
            } else {
                document.getElementById('searchLink').textContent = 'לא ניתן לבצע חיפוש עבור פרוטוקול זה';
                document.getElementById('searchLink').removeAttribute("href");
                document.getElementById('searchLink').removeAttribute("onclick");
            }
        }

        function openPopup() {
            var width = 1100;
            var height = 600;
            var left = (screen.width - width) / 2;
            var top = (screen.height - height) / 2;
            var newWindow = window.open(url, 'popupWindow', `width=${width},height=${height},top=${top},left=${left},resizable=yes,scrollbars=yes`);

            if (newWindow) {
                newWindow.focus();
                return false;
            }
        }

        window.onTicketLoaded = function(ticket) {
            console.log(ticket, 'color: red');
        }
    </script>
</body>

</html>