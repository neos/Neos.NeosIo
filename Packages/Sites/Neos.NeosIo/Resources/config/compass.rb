# Require any additional compass plugins here.

# Set this to the root of your project when deployed:
http_path = "/"
css_dir = "Public/Css"
sass_dir = "Private/Scss"
images_dir = "Public/Images"
javascripts_dir = "Public/Scripts"

# current config
# either use environment = :production or environment = :development
# some of the following options are set by default, but are listed here to show the difference between :production and :development
# for all config options see: http://compass-style.org/help/tutorials/configuration-reference/

environment = :development

output_style = (environment == :production) ? :compressed : :expanded
disable_warnings = (environment == :production) ? true : false
line_comments  = (environment == :production) ? false : true