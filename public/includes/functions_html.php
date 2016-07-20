<?php
$site_title = "Delts Manager";
$color_scheme = [
	"bgDefault"=>"663399",
	"bgHighlight"=>"7547a3",
	"bgDarker"=>"52297a",
	"colDefault"=>"ffff66",
	"colHighlight"=>"ffffd1"
];
$left = [];
$right = [];

if(user_authorized(USER_USER)) {
	$left[] = [
		"title"=>"House Duties",
		"type"=>TYPE_LINK,
		"content"=>"houseduties.php"
	];
	/*$left[] = [
		"title"=>"Parties",
		"type"=>TYPE_DROPDOWN,
		"content"=>[
			[
				"title"=>"Party Duties",
				"content"=>"parties.php"
			],
			[
				"title"=>"Party Invites",
				"content"=>"invites.php"
			]
		]
	];*/
	if(count(user_privileges()) > 1) {
		$content = [];
		if(user_authorized([USER_HOUSE_MANAGER,USER_CHECKER])) {
			$content[] = [
				"title"=>"Checkers",
				"content"=>"checker_dashboard.php"
			];
		}
		if(user_authorized([USER_HOUSE_MANAGER,USER_HONOR_BOARD])) {
			$content[] = [
				"title"=>"Punt Admin",
				"content"=>"admin_punts.php"
			];
		}
		if(user_authorized(USER_ADMIN)) {
			$content[] = [
				"title"=>"User Admin",
				"content"=>"admin_users.php"
			];
		}
		$right[] = [
			"title"=>"Officers",
			"type"=>TYPE_DROPDOWN,
			"content"=>$content
		];
	}
	$right[] = [
		"title"=>"Dashboard",
		"type"=>TYPE_LINK,
		"content"=>"dashboard.php"
	];
	if(user_certificates()) {
		$right[] = [
			"title"=>user_email(),
			"type"=>TYPE_LINK,
			"content"=>"javascript:void(0)"
		];
	} else {
		$right[] = [
			"title"=>user_email(),
			"type"=>TYPE_DROPDOWN,
			"content"=>[
				[
					"title"=>"Log Out",
					"content"=>"index.php?logout=logout"
				]
			]
		];
	}
} else {
	$left[] = [
		"title"=>"Log In",
		"type"=>TYPE_LINK,
		"content"=>"index.php"
	];
	$right[] = [
		"title"=>"Log In",
		"type"=>TYPE_HTML,
		"content"=>"
			<li class=\"dropdown\">
				<a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">Sign in <b class=\"caret\"></b></a>
				<ul class=\"dropdown-menu\">
					<form id=\"login_box\" action=\"index.php\" method=\"post\">
						<li>
							<div class=\"form-group\">
				            	<label for=\"login_email\">E-Mail</label>
				            	<input type=\"email\" name=\"login_email\" id=\"login_email\" class=\"form-control\" placeholder=\"Email\"/>
			            	</div>
			            </li>
			            <li>
				            <div class=\"form-group\">
				            	<label for=\"login_password\">Password</label>
				            	<input type=\"password\" name=\"login_password\" id=\"login_password\" class=\"form-control\" placeholder=\"Password\"/>
				            </div>
			            </li>
			            <li>
			            	<div class=\"form-inline\">
			            		<button class=\"btn btn-custom btn-change col-xs-12\" type=\"submit\" name=\"submit\" value=\"submit\">Log In</button>
			            	</div>
			            </li>
					</form>
				</ul>
			</li>
		"
	];
}


function processItem($item) {
	$rtn = "";
	switch($item["type"]) {
		case TYPE_LINK:
			$rtn = "<li";
			if($item["content"] == $_SERVER["THIS"]) $rtn .= " class=\"active\"";
			$rtn .= "><a href=\"{$item["content"]}\">{$item["title"]}</a></li>";
			return $rtn;
		case TYPE_DROPDOWN:
			$rtn = "<li class=\"dropdown\"><a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">{$item["title"]} <b class=\"caret\"></b></a><ul class=\"dropdown-menu\" role=\"menu\">";
			foreach($item["content"] as $i) {
				$rtn .= "<li><a href=\"{$i["content"]}\">{$i["title"]}</a></li>";
			}
			$rtn .= "</ul></li>";
			return $rtn;
		case TYPE_HTML:
			return $item["content"];
		default:
			return "ERROR: TYPE {$item["type"]} IS NOT A VALID TYPE";
	}
}

function head($title) {
	return head1($title) . head2();
}
function head1($title) {
	global $site_title;
	global $color_scheme;
	return "
	<!DOCTYPE html>
	<html>
	<head>
		<title>{$title} - {$site_title}</title>	
		<script src=\"https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js\"></script>
		<link rel=\"stylesheet\" href=\"https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css\">
		<script src=\"https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js\"></script>
		<style>
			.navbar-default {
			  background-color: #{$color_scheme["bgDefault"]};
			  border-color: #{$color_scheme["bgHighlight"]};
			}
			.navbar-default .navbar-brand {
			  color: #{$color_scheme["colDefault"]};
			}
			.navbar-default .navbar-brand:hover, .navbar-default .navbar-brand:focus {
			  color: #{$color_scheme["colHighlight"]};
			}
			.navbar-default .navbar-text {
			  color: #{$color_scheme["colDefault"]};
			}
			.navbar-default .navbar-nav > li > a {
			  color: #{$color_scheme["colDefault"]};
			}
			.navbar-default .navbar-nav > li > a:hover, .navbar-default .navbar-nav > li > a:focus {
			  color: #{$color_scheme["colHighlight"]};
			}
			.navbar-default .navbar-nav > .active > a, .navbar-default .navbar-nav > .active > a:hover, .navbar-default .navbar-nav > .active > a:focus {
			  color: #{$color_scheme["colHighlight"]};
			  background-color: #{$color_scheme["bgHighlight"]};
			}
			.navbar-default .navbar-nav > .open > a, .navbar-default .navbar-nav > .open > a:hover, .navbar-default .navbar-nav > .open > a:focus {
			  color: #{$color_scheme["colHighlight"]};
			  background-color: #{$color_scheme["bgHighlight"]};
			}
			.navbar-default .navbar-toggle {
			  border-color: #{$color_scheme["bgHighlight"]};
			}
			.navbar-default .navbar-toggle:hover, .navbar-default .navbar-toggle:focus {
			  background-color: #{$color_scheme["bgHighlight"]};
			}
			.navbar-default .navbar-toggle .icon-bar {
			  background-color: #{$color_scheme["colDefault"]};
			}
			.navbar-default .navbar-collapse,
			.navbar-default .navbar-form {
			  border-color: #{$color_scheme["colDefault"]};
			}
			.navbar-default .navbar-link {
			  color: #{$color_scheme["colDefault"]};
			}
			.navbar-default .navbar-link:hover {
			  color: #{$color_scheme["colHighlight"]};
			}

			@media (max-width: 767px) {
			  .navbar-default .navbar-nav .open .dropdown-menu > li > a {
			    color: #{$color_scheme["colDefault"]};
			  }
			  .navbar-default .navbar-nav .open .dropdown-menu > li > a:hover, .navbar-default .navbar-nav .open .dropdown-menu > li > a:focus {
			    color: #{$color_scheme["colHighlight"]};
			  }
			  .navbar-default .navbar-nav .open .dropdown-menu > .active > a, .navbar-default .navbar-nav .open .dropdown-menu > .active > a:hover, .navbar-default .navbar-nav .open .dropdown-menu > .active > a:focus {
			    color: #{$color_scheme["colHighlight"]};
			    background-color: #{$color_scheme["bgHighlight"]};
			  }
			}

			.btn-custom {
				background-color:#{$color_scheme["bgHighlight"]};
				border-color:#{$color_scheme["bgHighlight"]};
				color:#{$color_scheme["colDefault"]};
			}
			.btn-custom:hover,.btn-custom:focus,.btn-custom:active,.btn-custom.active {
				background-color:#{$color_scheme["bgDarker"]};
				color:#{$color_scheme["colHighlight"]};
			}
		</style>
		<link href=\"css/style.css\" rel=\"stylesheet\" type=\"text/css\"/>
		<script src=\"js/functions.js\"></script>
		<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">";
}
function head2() {
	global $left;
	global $right;
	global $site_title;
	$l = "";
	$r = "";
	foreach($left as $item) {
		$l .= processItem($item);
	}
	foreach($right as $item) {
		$r .= processItem($item);
	}

	$rtn = "</head>
	<body>
	   <div class=\"container\">
		<nav class=\"navbar navbar-default\" role=\"navigation\">
		  <div class=\"container-fluid\">
		    <div class=\"navbar-header\">
		      <button type=\"button\" class=\"navbar-toggle\" data-toggle=\"collapse\" data-target=\"#bs-example-navbar-collapse-1\">
		        <span class=\"sr-only\">Toggle navigation</span>
		        <span class=\"icon-bar\"></span>
		        <span class=\"icon-bar\"></span>
		        <span class=\"icon-bar\"></span>
		      </button>
		      <a class=\"navbar-brand\" href=\"index.php\">{$site_title}</a>
		    </div>
		    <div class=\"collapse navbar-collapse\" id=\"bs-example-navbar-collapse-1\">
		      <ul class=\"nav navbar-nav\">
		      {$l}
		      </ul>
		      <ul class=\"nav navbar-nav navbar-right\">
		      {$r}
		      </ul>
		    </div>
		  </div>
		</nav>";
	return $rtn;
}
function foot() {
	$date = date('Y');
	return "<hr/>
	<div class=\"text-center\" style=\"padding-bottom:15px;\">&copy;{$date} Erick Friis</div>
	</div></body></html>";
}
?>