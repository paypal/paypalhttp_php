streamFileFromWorkspace("GroveUtil.groovy")

listView('BraintreeHTTP PHP') {
  jobs {
    regex(/.*braintreehttp-php.*/)
  }
  columns {
    status()
      weather()
      name()
      lastSuccess()
      lastFailure()
      lastDuration()
      buildButton()
  }
}

["master"].each { buildBranch ->
  ["56", "70", "71"].each { phpVersion ->
    job("braintreehttp-php_${buildBranch}") {
      customWorkspace("workspace/braintreehttp-php")
        steps {
          shell("drake build")
          shell("drake test")
        }
      triggers {
        githubPush()
      }
      GroveUtil.setupDefaults(it)
        GroveUtil.setupGithub(it, "dx/braintreehttp-php", buildBranch)
        GroveUtil.setupSlack(it, "#auto-dx-clients", true)
    }
  }
}
