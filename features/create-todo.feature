@create
Feature: Test creating a ToDo item and also possible exceptions

    Scenario: Try to create a ToDo with missing "name" field
        When I add "Content-Type" header equal to "application/json"
        And I send a "POST" request to "/api/todos" with body:
        """
        {
            "description": "my first ToDo but I forgot the name"
        }
        """

        Then the response status code should be 400
        And the response should be in JSON
        And the JSON should be equal to:
        """
        {
             "errors": [
                  "name: This value should not be blank."
             ]
        }
        """

    Scenario: Try to create a ToDo with empty "name" field
        When I add "Content-Type" header equal to "application/json"
        And I send a "POST" request to "/api/todos" with body:
        """
        {
            "name": ""
        }
        """

        Then the response status code should be 400
        And the response should be in JSON
        And the JSON should be equal to:
        """
        {
             "errors": [
                  "name: This value should not be blank."
             ]
        }
        """

    Scenario: Try to create a ToDo with only spaces in "name" field
        When I add "Content-Type" header equal to "application/json"
        And I send a "POST" request to "/api/todos" with body:
        """
        {
            "name": "   ",
            "tasks": [
                {
                    "name": "    "
                }
            ]
        }
        """

        Then the response status code should be 400
        And the response should be in JSON
        And the JSON should be equal to:
        """
        {
            "errors": [
                "name: This value should not be blank.",
                "tasks[0].name: This value should not be blank."
            ]
        }
        """

    Scenario: Try to create a ToDo with unknown field
        When I add "Content-Type" header equal to "application/json"
        And I send a "POST" request to "/api/todos" with body:
        """
        {
            "name": "ToDo",
            "unknown": "unknown"
        }
        """

        Then the response status code should be 400
        And the response should be in JSON
        And the JSON should be equal to:
        """
        {
            "errors": [
                "Extra attributes are not allowed (\"unknown\" are unknown)."
            ]
        }
        """

    Scenario: Try to create a ToDo with id field
        When I add "Content-Type" header equal to "application/json"
        And I send a "POST" request to "/api/todos" with body:
        """
        {
            "name": "ToDo",
            "id": 10
        }
        """

        Then the response status code should be 400
        And the response should be in JSON
        And the JSON should be equal to:
        """
        {
            "errors": [
                "Extra attributes are not allowed (\"id\" are unknown)."
            ]
        }
        """

    Scenario: Try to create a ToDo with multiple validation errors
        When I add "Content-Type" header equal to "application/json"
        And I send a "POST" request to "/api/todos" with body:
        """
        {
            "name": "",
            "tasks": [
                {
                    "name": ""
                }
            ]
        }
        """

        Then the response status code should be 400
        And the response should be in JSON
        And the JSON should be equal to:
        """
        {
            "errors": [
                "name: This value should not be blank.",
                "tasks[0].name: This value should not be blank."
            ]
        }
        """
