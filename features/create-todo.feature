Feature: Test creating a ToDo item and also possible exceptions

    Scenario: Call a not found route
        When I add "Content-Type" header equal to "application/json"
        And I send a "GET" request to "/api/todos/not-found-route"
        Then the response status code should be 404

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

    Scenario: Successfully create a new ToDo
        When I add "Content-Type" header equal to "application/json"
        And I send a "POST" request to "/api/todos" with body:
        """
        {
            "name": "some name",
            "description": "some description"
        }
        """
        Then the response status code should be 201
        And the response should be in JSON
        And the JSON node "id" should be equal to the number 1
        And the JSON node "name" should be equal to the string "some name"
        And the JSON node "description" should be equal to the string "some description"
        And the JSON node "tasks" should have 0 elements
        And the JSON node "createdAt" should be a datetime
        And the JSON node "updatedAt" should be a datetime
