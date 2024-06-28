<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="utf-8" />
   <title>Phase1 Application Status</title>
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <meta name="description" content="To learn as much as I can, attain good grades and advance my education further. I believe that self-motivation and a strict routine has helped me achieve my goals so far, and I will use the same method in the future.">
   <style>
      body {
         font-family: "Yu Gothic", sans-serif;
      }

      .body-wrap {
         width: 100%;
         background-color: #FFFFFF;
      }

      .container {
         display: block !important;
         max-width: 600px !important;
         margin: 0 auto;
         /* Center the container */
      }

      .content {
         padding: 20px;
         margin-top: 20px;
      }

      .content-wrap {
         text-align: center;
         /* Center text inside the content */
         line-height: 20px;
         padding: 30px;
         background: #F2F2F2;
         border-top: 6px solid #6FC6CC;
         max-width: 600px;
         /* Optional: Set a maximum width for better control */
         margin: 0 auto;
         /* Center the content-wrap within its container */
      }

      .schoolname {
         font-size: 15px;
         color: #343556;
         font-weight: 800;
         margin-top: -37px;
         text-align: right;
      }

      hr {
         margin-top: 1.5rem;
         margin-bottom: 0.5rem;
         border: 0;
         border-top: 2px solid #D9D9D9;
      }

      .head {
         font-family: "Yu Gothic", sans-serif;
         font-weight: 700;
         line-height: 28px;
         letter-spacing: 0em;
         text-align: left;
         /* Center header text */
         color: #000000;
         margin-bottom: 20px;
      }

      P {
         font-family: "Yu Gothic", sans-serif;
         font-size: 14px;
         font-weight: 500;
         line-height: 18.4px;
         letter-spacing: 0em;
         text-align: justify;
         color: #000000;
      }

      li {
         font-family: "Yu Gothic", sans-serif;
         font-size: 14px;
         font-weight: 500;
         line-height: 18.4px;
         letter-spacing: 0em;
         text-align: justify;
         color: #000000;
      }

      .header-container {
         display: flex;
         align-items: center;
         /* Vertically center the image and text */
         justify-content: space-between;
         /* Space between the image and text */
      }

      .header {
         display: block;
         /* Ensure the image is treated as a block element */
         border-radius: 50%;
         /* To make it rounded */
         margin-right: 10px;
         /* Space between the image and the text */
         max-width: 50px;
         /* Adjust as needed */
         height: auto;
         /* Maintain aspect ratio */
      }
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
                              <td class="header-container">
                                 <img src="https://api.suzen.school/common-asset/images/logo_jskl.jpeg" class="header" alt="School Logo">
                              </td>
                           </tr>
                           <tr>
                              <td>
                                 <p class="schoolname">Japanese School Kuala Lumpur</p>
                              </td>
                           </tr>
                           <tr>
                              <td>
                                 <hr style="margin-top:3px;">
                                 <h4 class="head">
                                    {{ $data['parent_name']  }}様
                                 </h4>
                              </td>
                           </tr>
                           <tr>
                              <td>
                                 <p>いつもＳｕｚｅｎご利用いただきありがとうございます。</p>
                                 <p>ご提出した入学願書 （ {{ $data['phase'] }} ）が{{ $data['status']  }}されました。</p>
                                 <p>下記の手順に従い、引き続き入学願書（Phase２）へ必要な情報をご入力をお願いいたします。</p>
                                 <ol>
                                    <li style="text-align:left;">Ｓｕｚｅｎへログイン</li>
                                    <ul style="list-style-type:none; padding-left:20px;">
                                       <li>a. これから編入準備の保護者様　：　ゲストポータル　 <a href="{{  $data['guest_link']  }}">{{ $data['guest_link']  }}</a></li>
                                       <li>b. 在籍している園児・生徒の保護者様：保護者ポータル　 <a href="{{  $data['parent_link']  }}">{{ $data['parent_link']  }}</a></li>
                                    </ul>
                                    <li style="text-align:left;">サイドメニューの「入学願書」をクリック</li>
                                    <li style="text-align:left;">サイドメニューの「リスト」をクリック</li>
                                    <li style="text-align:left;">リストにて情報を入力したい入学願書の「編集」をクリック</li>
                                    <li style="text-align:left;">上部にある「Phase2」ボタンをクリックし、必要な項目に情報をご記入ください</li>
                                 </ol>
                              </td>
                           </tr>
                           <tr>
                              <td>
                                 <p>以上。</p>
                              </td>
                           </tr>
                        </table>
                        <!-- End Header-->
                        <!-- Footer Table-->
                        <table>
                           <tr>
                              <td>
                                 <hr style="width: 552px; height: 1px;margin-top:3px;">
                              </td>
                           </tr>
                           <tr>
                              <td>
                                 <h4 class="head">
                                    Dear {{ $data['parent_name']  }}
                                 </h4>
                              </td>
                           </tr>
                           <tr>
                              <td>
                                 <p>Thank you for using Suzen.</p>
                                 <p>The admission application （ {{ $data['phase'] }} ） you submitted has been {{ $data['status']  }}.</p>
                                 <p>Please follow the steps below to procced your Phase 2 application</p>
                                 <ol>
                                    <li style="text-align:left;">Login to Suzen</li>
                                    <ul style="list-style-type:none; padding-left:20px;">
                                       <li>a. For newcomer’s parent: guest portal <a href="{{  $data['guest_link']  }}">{{ $data['guest_link']  }}</a></li>
                                       <li>b. For enrolled student’s parent: parent portal <a href="{{  $data['parent_link']  }}">{{ $data['parent_link']  }}</a></li>
                                    </ul>
                                    <li style="text-align:left;">Click “Application” on the side menu</li>
                                    <li style="text-align:left;">Click “List” on the side menu</li>
                                    <li style="text-align:left;">Click "Edit" button on the admission application you want to check</li>
                                    <li style="text-align:left;">Click the "Phase2" button at the top and fill in the required information.</li>
                                 </ol>
                              </td>
                           </tr>
                           <tr>
                              <td>
                                 <p>Thank you</p>
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