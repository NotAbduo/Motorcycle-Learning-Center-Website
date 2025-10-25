<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['sign_name']) || !isset($_SESSION['sign_national_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['accept'])) {
        header("Location: signature_pad.php");
        exit();
    } else {
        $error = "You must accept the terms and conditions to proceed.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Motorcycle License Agreement</title>
    <link rel="stylesheet" href="css/license.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

</head>
<body>

<a href="<?= $_SESSION['role'] == 'admin' ? 'admin_dashboard.php' : ($_SESSION['role'] == 'supervisor' ? 'supervisor_dashboard.php' : 'user_dashboard.php') ?>" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>



<div class="dashboard-container">

    <h1>Agreement for Training Services (Terms and Conditions)</h1>

    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div style="text-align: left; color: #333;">
		<a href="license_arabic.php" class="lang-button">العربية</a>
            <p><strong>Motorcycle Learning Center L.L.C (the Center)
Address: Oman Automobile Association
C.R.1313941, P.O. Box:1313, P.C: 111 CPO, Contact
+968 94445396</strong></p>
            <p>By proceeding with this application, you agree to the following terms and conditions:</p>
            <ul style="margin-top: 15px; margin-bottom: 15px; line-height: 1.6;">
                <li>The Trainee must have a valid motorcycle
learning book issued by the Licensing
Department (General Directorate of Traffic of
the Royal Oman Police).</li>
                <li>The Center agrees to provide Motorcycle
Training to the customer at times to be agreed
between the customer and the center to provide
the customer with training programmes
mentioned in the annex to this agreement.
</li>
                <li>The Center agrees to provide course reference
book and notes as applicable.
</li>
                <li>The Customer agrees to pay the fees according
to the training programme mentioned in the
appendix of this agreement.
</li>
                <li>The Customer confirms he accepts the Center's
Policy regarding confidentiality, the preservation
of center documentation and undertakes not to
use any documents developed for the purpose
of Motorcycle Training or first Aid Training
outside the Company. The Customer further
agrees not to disclose information about Center
Training Service, its method of operation or
center business to anyone not authorized to
receive it.
</li>
                <li>The Customer agree to comply with the Center's
Health and Safety Policy at all times. The
Customer agrees to, as far as practicable keep
a safe environment for trainees and will
conduct training in a safe manner. The Center's
Health and Safety Policy is displayed in the
training center.
</li>

                <li>The Customer agrees to conduct him/herself in
an appropriate and professional manner at all
the time so as not to bring the Company into
disrepute and to promote the service of the
Company.
</li>
                <li>The Customer agrees when using Motorcycle
and equipment, the property of the Center, to
safeguard these against damage or misuse.
</li>
                <li>The Customer agree on broadcasting or
publishing any materials for outreach or
marketing purposes, including photographs and
footage, on social media channels, television,
newspapers or any other publishing means not
mentioned above (Females are excluded from
this condition).
</li>
                <li>Training bookings are only confirmed on receipt
of full payment. 20% of the amount paid will be
deducted if the trainee requests to cancel the
training for personal reasons. In the event of
cancellation through illness every effort will be
made by the center to accommodate the
Customer on another suitable date.
</li>

            </ul>
			            <p><strong>11. LIABILITIES
</strong></p>

<p>The Center accepts no responsibility for any
accidents, injuries, loss or theft of any property
incurred whilst on the course or at any time. The
action of any customer which results in any criminal
or Civil action either within the course or at any
times subsequently are the sole responsibility of the
customer. It is the customers responsibility to ride
within the law. If the Customer's responsibility to
ensure that it is in a safe and road worthy condition,
carries the appropriate insurance and that
appropriate clothing and safety equipment is worn.
(e.g. helmet, gloves, boots and leather). The
Customer is, as far as reasonably practicable to
carry out the instructions given by the center
instructor at all the times. The Center reserve the
right to refuse instruction to any rider who does not
comply with these Terms and Conditions.
</p>
<ul style="margin-top: 15px; margin-bottom: 15px; line-height: 1.6;">
                <li>The Customer must report and disclose his/her
health condition to the Center or Trainer if he/
she suffers from any diseases before starting
the training.
</li>
                <li>The Customer must send the copy of the
license to the Center after successfully passing
the test.
</li>
                <li>The parent is responsible for his son/daughter
during training on the YOUNG RIDER PROGRAM
and signs the responsibility form by disclaiming
the Center.
</li>
                <li>The Customer will bear all traffic fines when
riding Center's motorcycle.
</li>
                <li>The Customer will bear the cost of repairing of
Center's motorcycle if an accident occurs,
whether it is traffic or during training program.

</li>

                <li>The Trainee is obligated to notify the Center 3
days before the date of the license test.
</li>
                <li>The conditions mentioned at the bottom of
each training program apply.
</li>

            </ul>
        </div>

        <label style="display: block; margin-bottom: 10px;">
            <input type="checkbox" name="accept" required>
            I accept the terms and conditions.
        </label>

        <button type="submit">Sign and Proceed</button>
    </form>
</div>

</body>
</html>
