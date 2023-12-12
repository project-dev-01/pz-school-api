<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <title>Email Event Invitation Template</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Paxsuzen School is a premier educational institution that offers quality education to students of all ages. Our curriculum is designed to prepare future leaders for success in the global marketplace.">
  <meta name="keywords" content="Paxsuzen School, education, future leaders, curriculum">
  <meta content="Paxsuzen" name="author" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <!-- CSRF Token -->
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <!-- App favicon -->
  <link rel="shortcut icon" href="{{ 'http://localhost/paxsuzen-api-dev/public/common-asset/images/favicon.ico' }}">
  <!-- App css -->
  <link href="{{ 'http://localhost/school-management-system/public/css/bootstrap.min.css' }}" rel="stylesheet" type="text/css" id="bs-default-stylesheet" />
  <link href="{{ 'http://localhost/school-management-system/public/css/app.min.css' }}" rel="stylesheet" type="text/css" id="app-default-stylesheet" />
  <!-- icons -->
  <link href="{{ 'http://localhost/school-management-system/public/css/icons.min.css' }}" rel="stylesheet" type="text/css" />
  <link href="{{ 'http://localhost/school-management-system/public/css/custom-minified/opensans-font.min.css' }}" rel="stylesheet" type="text/css" />
  <!-- <link href="{{ 'http://localhost/school-management-system/public/css/custom/emailnotification.css' }}" rel="stylesheet" type="text/css" /> -->
  <style>
    .body-wrap 
{
  width: 100%;
  background-color: #FFFFFF;
}

.container 
{
  display: block !important;
  max-width: 600px !important;
}

.content 
{
  padding: 20px;
  margin-top: 20px;
}

.content-wrap 
{
  text-align: justify;
  line-height: 20px;
  padding: 30px;
  background: #F2F2F2;
  border-top: 6px solid #2F2F8F;
}

.footerlogo 
{
  float: right;
  height: 80px;
  margin-top: -30px;
}

.schoolname 
{
  font-size: 15px;
  color: #343556;
  font-weight: 800;
  margin-top: -37px;
  text-align: right;
}

hr 
{
  margin-top: 1.5rem;
  margin-bottom: 0.5rem;
  border: 0;
  border-top: 1px solid #D9D9D9;
}

.head 
{
  font-family: Arial;
  font-size: 24px;
  font-weight: 700;
  line-height: 28px;
  letter-spacing: 0em;
  text-align: left;
  color: #000000;
  margin-bottom: 20px;
}

.heads 
{
  font-family: Arial;
  font-size: 16px;
  font-weight: 700;
  line-height: 35px;
  letter-spacing: 0em;
  text-align: left;
  color: #000000;
}

P 
{
  font-family: Arial;
  font-size: 15px;
  font-weight: 500;
  line-height: 18.4px;
  letter-spacing: 0em;
  text-align: justify;
  color: #000000;
}

.footerfont 
{
  font-family: Arial;
  font-size: 12px;
  font-weight: 500;
  line-height: 18px;
  letter-spacing: 0em;
  text-align: left;
  color: #000000;
}

.gmail-table 
{
  margin-bottom: 10px;
  width: 100%;
  border-collapse: collapse;
  overflow: hidden;
  box-shadow: 0 0 0 1px #7E7E7E;
  border-radius: 16px;
  border: 1px;
}

.gmail-table thead th 
{
  background: #2F2F8F;
  padding: 10px;
  text-align: left;
  color: #FFFFFF;
  font-family: Arial;
  font-size: 16px;
  font-weight: 700;
  line-height: 18px;
  letter-spacing: 0em;
  text-align: left;
}

.gmail-table tbody td 
{
  padding: 13px;
  font-family: Arial;
  font-size: 14px;
  font-weight: 500;
  line-height: 18px;
  letter-spacing: 0em;
  text-align: left;
  color: #000000;
  background: #FFFFFF;
}

.idcardleft 
{
  background: #FFFFFF;
}

.idcardright 
{
  background: #2F2F8F;
  width: 190px;
}

.idcard-table 
{
  width: 552px;
  height: 50px;
  margin-bottom: 15px;
  border-collapse: collapse;
  overflow: hidden;
  box-shadow: 0 0 0 1px #7E7E7E;
  border-radius: 16px;
  border: 1px;
}

.idcard-table tbody td 
{
  padding: 13px;
  font-family: Arial;
  font-size: 14px;
  font-weight: 600;
  line-height: 18px;
  letter-spacing: 0em;
  text-align: left;
  color: #FFFFFF;
}

.card 
{
  width: 552px;
  height: 146px;
  padding: 24px;
  border-radius: 8px;
  border: 1px;
  gap: 14px;
  box-shadow: 0 0 0 1px #7E7E7E;
}

.btn-primary-bl 
{
  width: 179px;
  height: 48px;
  padding: 14px 20px 16px 20px;
  border-radius: 8px;
  gap: 8px;
  background: #2F2F8F;
  color: #FFFFFF;
  margin-top: -15px;
}

.forgetpassword 
{
  width: 552px;
  height: 128px;
  padding: 24px;
  border-radius: 8px;
  border: 1px;
}

.reset 
{
  width: 149px;
  height: 48px;
  padding: 16px 20px 16px 20px;
  border-radius: 8px;
  background: #2F2F8F;
}

/* Start of Newsletter email */
.newsletter 
{
  width: 552px;
  height: 982px;
  padding: 24px;
  border-radius: 8px;
  border: 1px;
}

.one 
{
  width: 504px;
  height: 138px;
  padding: 6px 25px 24px 16px;
  border-radius: 8px;
  background: #EEF1F6;
}

.numbers 
{
  width: 23px;
  height: 90px;
  font-family: Arial;
  font-size: 40px;
  font-weight: 700;
  line-height: 46px;
  letter-spacing: 0em;
  text-align: left;
  color: #2F2F8F;
  padding: 0px 10px 49px 0px;
}

.two 
{
  width: 504px;
  height: 102px;
  padding: 24px 16px 24px 16px;
  border-radius: 8px;
  background: #EEF1F6;
}

.three 
{
  width: 504px;
  height: 94px;
  padding: 24px 16px 24px 16px;
  border-radius: 8px;
  background: #EEF1F6;
}

.padding 
{
  padding: 20px 0px 0px 0px;
}

.text 
{
  font-family: Arial;
  font-size: 15px;
  font-weight: 500;
  line-height: 18.4px;
  letter-spacing: 0em;
  text-align: justify;
  color: #000000;
  margin-top: -15px;
}

/* End of Newsletter email */

/* Start of Educational_content email */
.educational 
{
  width: 552px;
  height: 822px;
  padding: 24px;
  border-radius: 8px;
  border: 1px;
}

/* End of Educational_content email */

/* Start of Special_offer email */
.limitedoffer 
{
  width: 552px;
  height: 160px;
  padding: 24px;
  border-radius: 8px;
  align-items: center;
}

.specialoffer 
{
  width: 179.67px;
  height: 112px;
}

.specialoffers 
{
  width: 552px;
  height: 388px;
  padding: 24px;
  border-radius: 8px;
}

.stwo 
{
  width: 504px;
  height: 104px;
  padding: 16px;
  border-radius: 8px;
  background: #EEF1F6;
}

/* End of Special_offer email */

/* Start of Promo email */
.promo 
{
  width: 552px;
  height: 126px;
  padding: 24px;
  border-radius: 8px;
}

.specialpromo 
{
  width: 502px;
  height: 78px;
}

.promos 
{
  width: 552px;
  height: 948px;
  padding: 24px;
  border-radius: 8px;
  border: 1px;
}

/* End of Promo email */

/* Start Invoice email */

.invoice 
{
  height: 200px;
  margin-bottom: 10px;
  width: 100%;
  border-collapse: collapse;
  overflow: hidden;
  box-shadow: 0 0 0 1px #7E7E7E;
  border-radius: 16px;
  border: 1px;
}

.invoice thead th 
{
  height: 50px;
  background: #2F2F8F;
  padding: 10px;
  text-align: left;
  color: #FFFFFF;
  font-family: Arial;
  font-size: 16px;
  font-weight: 700;
  line-height: 18px;
  letter-spacing: 0em;
  text-align: left;
}

.invoice tbody td 
{
  padding: 13px;
  font-family: Arial;
  font-size: 14px;
  font-weight: 500;
  line-height: 18px;
  letter-spacing: 0em;
  text-align: left;
  color: #000000;
  background: #FFFFFF;
}

.status 
{
  padding: 16px;
  background: #DBDEE5;
  color: #000000;
  font-family: Arial;
  font-size: 16px;
  font-weight: 400;
  line-height: 18px;
  letter-spacing: 0em;
  text-align: left;
  width: 148px;
}

.totalamount
{
  width: 148px;
  padding: 16px;
  background: #DBDEE5;
  color: #000000;
  font-family: Arial;
  font-size: 16px;
  font-weight: 400;
  line-height: 18px;
  letter-spacing: 0em;
  text-align: left;
}

.unpaid 
{
  width: 148px;
  padding: 16px;
  background: #070739;
  color: #FFFFFF;
  font-family: Arial;
  font-size: 16px;
  font-weight: 400;
  line-height: 18px;
  letter-spacing: 0em;
  text-align: left;
}

/* End Invoice email */

/* Start Anniversary Celebration */
.headanniversary 
{
  font-family: Arial;
  font-size: 21px;
  font-weight: 700;
  line-height: 28px;
  letter-spacing: 0em;
  text-align: left;
  color: #000000;
  margin-bottom: 20px;
}

.event 
{
  width: 552px;
  height: 305px;
  padding: 24px;
  border-radius: 8px;
  box-shadow: 0 0 0 1px #7E7E7E;
  gap: 24px;
  background-color: #FFFFFF;
  margin-bottom: 15px;
}

.invited 
{
  width: 504px;
  height: 110px;
  border-radius: 8px;
  text-align: center;
}

.eventdetails 
{
  font-family: Arial;
  font-size: 20px;
  font-weight: 700;
  line-height: 38px;
  letter-spacing: 0em;
  text-align: center;
}

.details 
{
  font-family: Arial;
  font-size: 15px;
  font-weight: 500;
  line-height: 11.4px;
  letter-spacing: 0em;
  text-align: center;
  color: #000000;
}

/* End Anniversary Celebration */

/* Start Event Invitation Celebration */
.invitation 
{
  width: 552px;
  height: 271px;
  padding: 24px;
  border-radius: 8px;
  box-shadow: 0 0 0 1px #7E7E7E;
  gap: 24px;
  background-color: #FFFFFF;
  margin-bottom: 15px;
}

.invitationdetails 
{
  font-family: Arial;
  font-size: 15px;
  font-weight: 500;
  line-height: 9px;
  letter-spacing: 0em;
  text-align: center;
  color: #000000;
}

/* End Event Invitation Celebration */

/* Start Reminder Event Celebration */
.eventreminder 
{
  width: 552px;
  height: 283px;
  padding: 24px;
  border-radius: 8px;
  box-shadow: 0 0 0 1px #7E7E7E;
  gap: 24px;
  background-color: #FFFFFF;
  margin-bottom: 15px;
}

/* End Reminder Event Celebration */

/* Start Login Credential */
.login 
{
  width: 552px;
  height: 170px;
  padding: 24px;
  border-radius: 8px;
  border: 1px;
  gap: 14px;
  box-shadow: 0 0 0 1px #7E7E7E;
  background-color: #FFFFFF;
  margin-bottom: 15px;
}

.texts 
{
  font-family: Arial;
  font-size: 15px;
  font-weight: 500;
  line-height: 25px;
  letter-spacing: 0em;
  text-align: justify;
  color: #000000;
}

/* End Login Credential */

/* Start Birthday*/
.birthday 
{
  width: 552px;
  height: 211px;
  padding: 24px;
  border-radius: 8px;
  border: 1px;
  gap: 14px;
  box-shadow: 0 0 0 1px #7E7E7E;
  background-color: #FFFFFF;
  margin-bottom: 15px;
}

.birthdayimage 
{
  width: 500px;
  height: 163px;
  top: 116px;
  left: 2636px;
}

/* End Birthday*/

/* Start Feedback Followup */
.feedback 
{
  width: 552px;
  height: 288px;
  padding: 24px;
  border-radius: 8px;
  border: 1px;
  gap: 14px;
  box-shadow: 0 0 0 1px #7E7E7E;
  background-color: #FFFFFF;
  margin-bottom: 15px;
}

.followup 
{
  width: 316px;
  height: 270px;
  margin-left: 100px;
}

/* End Feedback Followup*/

/* Start Special Announcement*/
.announcement 
{
  width: 552px;
  height: 380.14px;
  padding: 24px;
  border-radius: 8px;
  border: 1px;
  gap: 24px;
  box-shadow: 0 0 0 1px #7E7E7E;
  background-color: #FFFFFF;
  margin-bottom: 15px;
  text-align: center;
}

.special 
{
  width: 243px;
  height: 211.141845703125px;
}

/* End Special Announcement */

/* Start Thank you */
.thankyou 
{
  width: 552px;
  height: 295px;
  padding: 24px;
  border-radius: 8px;
  border: 1px;
  gap: 24px;
  box-shadow: 0 0 0 1px #7E7E7E;
  background-color: #FFFFFF;
  margin-bottom: 15px;
  text-align: center;
}

.thank 
{
  width: 330px;
  height: 247px;
}

/* End Thank you */

/*  Start Notification Action Required */
.emailnotification 
{
  width: 552px;
  height: 305px;
  padding: 24px;
  border-radius: 8px;
  box-shadow: 0 0 0 1px #7E7E7E;
  gap: 24px;
  background-color: #FFFFFF;
  margin-bottom: 15px;
}

/* End Notification Action Required */

/*  Start Homework Submission */
.assign 
{
  background: #2F2F8F;
  width: 240px;
}

/* End Homework Submission */


/* End Responsive */
  </style>
</head>

<body>
  <table class="body-wrap">
    <tr>
      <td class="container">
        <div class="content">
          <table>
            <tr>
              <td class="content-wrap">
                <!-- Start Header-->
                <table width="100%">
                  <tr>
                    <td>
                      <img src="{{ $message->embed($school_image) }}" class="mr-2 rounded-circle header">
                      <p class="schoolname">{{$school_name}}</p>
                      <hr>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <h4 class="head">Invitation For [Event Name]</h4>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <p><b>Dear [Username],</b></p>
                      <p><b>Save The Date!</b></p>
                  </tr>
                </table>
                <!-- End Header-->
                <!-- Card-->
                <div class="invitation">
                  <img src="{{ $message->embed('http://localhost/school-management-system/public/images/emailnotification/invited.png') }}" class="invited">
                  <h4 class="eventdetails">[Event Name]</h4>
                  <p class="invitationdetails"><b>Date:</b> 12 January 2023</p>
                  <p class="invitationdetails"><b>Location:</b> School Hall</p>
                  <p class="invitationdetails"><b>Theme:</b> School Hall</p>

                </div>
                <!-- End card-->
                <!-- Footer Table-->
                <table>
                  <tr>
                    <td>
                      <p>You're invited to our enchanting <b>Annual Day Event</b> at <b>[{{$school_name}}]!</b></p>
                      <p>Join us for an unforgettable evening of mesmerizing performances and heartwarming student talent.</p> 
                      <p>This year's theme: <b>[Event Theme],</b> promising a joyous celebration.</p>
                      <p>Let's make memories on this special day!</p>
                      <h4 class="heads">Best regards,</h4>
                      <h6>{{$school_name}}</h6>
                      <hr style="width: 552px; height: 1px;">
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <p class="footerfont">For help & support, kindly use contact information below.</p>
                      <img src="{{ $message->embed($school_image) }}" class="mr-2 rounded-circle footerlogo">
                      <p class="footerfont">schoolhelp@gmail.com</p>
                      <p class="footerfont" style="line-height: 1px;">+60 1234-2345-122</p>
                    </td>
                  </tr>
                </table>
                <!--End Footer Table-->
              </td>
            </tr>
          </table>
        </div>
      </td>
    </tr>
  </table>
</body>

</html>