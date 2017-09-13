require 'securerandom'

#### releasinator config ####
configatron.product_name = "BraintreeHttp-PHP"

# List of items to confirm from the person releasing.  Required, but empty list is ok.
configatron.prerelease_checklist_items = [
  "Sanity check the master branch."
]

def test
  ["56", "70", "71"].each do |version|
    _test_with_dockerfile(version)
  end
end

def _test_with_dockerfile(php_version)
  tag = SecureRandom.uuid
  CommandProcessor.command("docker build -f tests/Dockerfiles/php#{php_version}/Dockerfile -t #{tag} .")
  CommandProcessor.command("docker run #{tag} ./vendor/bin/phpunit", live_output=true)
end

configatron.custom_validation_methods = [
  method(:test)
]

# there are no separate build steps for BraintreeHttp-PHP, so it is just empty method
def build_method
end

# The command that builds the sdk.  Required.
configatron.build_method = method(:build_method)

# Creating and pushing a tag will automatically create a release, so it is just empty method
def publish_to_package_manager(version)
end

# The method that publishes the sdk to the package manager.  Required.
configatron.publish_to_package_manager_method = method(:publish_to_package_manager)

def create_downloadable_zip(version)
  sleep(120)
  CommandProcessor.command("rm -rf temp; mkdir temp; cd temp; composer clear-cache; composer require 'braintree/braintreehttp:#{version}'", live_output=true)
  CommandProcessor.command("cd temp; mv vendor BraintreeHttp-PHP", live_output=true)
  CommandProcessor.command("cd temp; zip -r BraintreeHttp-PHP-#{version}.zip BraintreeHttp-PHP", live_output=true)
end

def add_to_release(version)
  sleep(30)
  Publisher.new(@releasinator_config).upload_asset(GitUtil.repo_url, @current_release, "temp/BraintreeHttp-PHP-#{version}.zip", "application/zip")
end

configatron.post_push_methods = [
  method(:create_downloadable_zip),
  method(:add_to_release)
]

def wait_for_package_manager(version)
end

# The method that waits for the package manager to be done.  Required
configatron.wait_for_package_manager_method = method(:wait_for_package_manager)

# Whether to publish the root repo to GitHub.  Required.
configatron.release_to_github = true

