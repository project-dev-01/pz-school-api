<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8" />
	<title>Frequently Asked Questions</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="Paxsuzen School is a premier educational institution that offers quality education to students of all ages. Our curriculum is designed to prepare future leaders for success in the global marketplace.">
	<meta name="keywords" content="Paxsuzen School, education, future leaders, curriculum">
	<meta content="Paxsuzen" name="author" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<!-- CSRF Token -->
	<meta name="csrf-token" content="{{ csrf_token() }}" />
	<!-- App favicon -->
	<link rel="shortcut icon" href="{{ config('constants.image_url').'/public/common-asset/images/favicon.ico' }}">
	<!-- App css -->
	<link href="{{ asset('public/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" id="bs-default-stylesheet" />
	<link href="{{ asset('public/css/app.min.css') }}" rel="stylesheet" type="text/css" id="app-default-stylesheet" />
	<!-- icons -->
	<link href="{{ asset('public/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
	<link href="{{ asset('public/css/custom-minified/opensans-font.min.css') }}" rel="stylesheet" type="text/css" />
	<script>window.UserHelpPublicProjectID="Y7YyGqyq2"</script>
        <script src="https://run.userhelp.co" async></script>
</head>
<style>
	.h1,
	.h2,
	.h3,
	.h4,
	.h5,
	.h6,
	h1,
	h2,
	h3,
	h4,
	h5,
	h6 {
		font-family: Open sans-serif;
		margin: revert;
		font-weight: 800;
	}

	.btn-primary-bl {
		color: #fff;
		border-color: #0ABAB5;
		background-color: #6FC6CC;
		text-align: center;
		border-radius: 10px;
	}
</style>

<body style="font-family: Open Sans; width: 100% !important; height: 100%; line-height: 1.6em; background-color: #f6f6f6; margin: 0;" bgcolor="#f6f6f6">

	<table class="body-wrap" style="width: 100%;">
		<tr>
			<td class="container" width="800" style="display: block !important; max-width: 800px !important;" valign="top">
				<div class="content" style="padding:20px; margin-top: 20px;">
					<table class="main" width="100%" cellpadding="0" cellspacing="0" itemprop="action" itemscope itemtype="http://schema.org/ConfirmAction">
						<tr>
							<td class="content-wrap" style="text-align: justify;
    line-height: 25px;padding: 30px;border: 3px solid #4fc6e1;background-color: #fff;" valign="top">
								<table width="100%">
									<tr>
										<td>
											<img src="{{ config('constants.image_url').'/public/common-asset/images/Suzen-app-logo.png' }}" class="mr-2 rounded-circle" alt="">
											<p style="font-size: 15px; color: #343556; font-weight: 800; margin-top: -37px; text-align: right; margin-bottom: 37px;">{{ $school_name }}</p>
										</td>
									</tr>
									<tr>
										<td>
											<table width="100%" border="0" align="left">
												<tr>
													<td>
														<h4 style="text-align:center;">Subject : {{ $subject }}</h4>
														<h5>Dear {{ $name }},</h5>
														<p>{{ $remarks }}</p>
														<p>Best Regards,</p>
														<h6>{{ $school_name }}</h6>
													</td>
												</tr>

											</table>
										</td>

										</tbody>
								</table>

							</td>
						</tr>
					</table>
				</div>
			</td>
		</tr>
		</tbody>
	</table>
</body>

</html>