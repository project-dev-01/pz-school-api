<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8" />
	<title>Guest Verify Email</title>
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
												{{  $data['email']  }}様
											</h4>
										</td>
									</tr>
									<tr>
										<td>
											<p>ご利用のメールアドレスの認証が正しく行われました。</p>
											<p>下記の詳細に従い、ゲストポータルへログインしてください。</p>
											<p>ゲストポータルリンク: <a href="{{ $data['link'] }}">{{ $data['link']  }}</a></p>
											<p>ログインアカウント: {{  $data['email'] }}</p>
											<p>パスワード: {{ $data['password'] }}</p>
										</td>
									</tr>
									<tr>
										<td>
											<p>ログイン後サイドメニューの「入学願書」の「追加」より、</p>
											<p>入学願書を提出してください。</p>
										</td>
									</tr>
									<tr>
										<td>
											<p>入学手続きの詳細に関してもう一度ご覧になりたい方は、</p>
											<p>下記のリンクよりご確認ください。</p>
											<p><a href="https://jskl.edu.my/wp-content/uploads/2024/04/JSKL%E5%85%A5%E5%9C%92%E3%83%BB%E5%85%A5%E5%AD%A6%E6%89%8B%E7%B6%9A%E3%81%8D2024.pdf?_sm_nck=1">ご入園・ご入学手続き</a></p>
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
												Dear {{ $data['email']  }}
											</h4>
										</td>
									</tr>
									<tr>
										<td>
											<p>Your email address has been successfully verified.</p>
											<p>Please log into the guest portal using the details below.</p>
											<p>Guest portal link: <a href="{{ $data['link'] }}">{{ $data['link'] }}</a></p>
											<p>Login account: {{  $data['email']  }}</p>
											<p>Password: {{ $data['password'] }}</p>
										</td>
									</tr>
									<tr>
										<td>
											<p>After logging in, click "Add" under "Application " on the side menu,</p>
											<p>To submit your admission application.</p>
										</td>
									</tr>
									<tr>
										<td>
											<p>If you would like to see the details of the admission procedure again, </p>
											<p>Please check from the link below.</p>
											<p><a href="https://jskl.edu.my/wp-content/uploads/2024/04/JSKL%E5%85%A5%E5%9C%92%E3%83%BB%E5%85%A5%E5%AD%A6%E6%89%8B%E7%B6%9A%E3%81%8D2024.pdf?_sm_nck=1">ご入園・ご入学手続き</a></p>
										</td>
									</tr>
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