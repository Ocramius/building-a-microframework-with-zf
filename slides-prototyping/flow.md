# intro

 - who am I
 - what do I do?
 - what is this talk about?
    - not many practical examples
    - more focus on the "why"
    
# what is a micro-framework?

 - ask the audience
 - a micro-framework is a non-invasive framework
 - a micro-framework is minimal, yet structured
 - a micro-framework encourages good coding practices, yet maintaining code concise
 - a micro-framework reduces focus on application logic, increasing focus on domain logic
   
# where do we use a micro-framework?

 - small bounded contexts
 - prototyping around an existing domain
 - web pages? Nah, use static HTML for that.
 
# types of micro-framework?

 - the following are all micro-frameworks:
    - symfony/console is a micro-framework
    - silex
    - penny
    - lumen
    - zendframework/zend-expressive
    - slim

# let's make an example

 - we're in Vegas
 - let's design a domain!
    - Poker!
    - (by the way, I don't know the rules of Poker)
 - Poker game
    - for simplicity, the game is serializable
    - we will store it in a fixed file location (again, simplicity)
 - Player (part of the table)
    - for simplicity, cannot leave
    - for simplicity, has an initial fixed amount of chips
 - Player has actions, actions may fail or succeed
    - actions happen through the table:
       - `Table#join($player)`
          - gives us a `PlayerToken`
       - `Table#pass($playerToken)`
       - `Table#bet($playerToken, $amount)`
          - only the owner of the token can execute these actions!
 - Table status is displayed (possible actions, round, winner)
    - `Table#showPossibleActions()`
       - returns a `PossibleActions` VO
          - with players and what they can do
          - only the current player has a possible action
    - `Table#showAmounts()`
       - returns a `Player` VO
          - with players and their chips
    - `Table#getPlayerCards($playerToken)`
       - allows reading the cards of a player by his token
       - No token = can't read cards
       - security is part of the domain
          - in poker, you can't read somebody else's cards!

# let's code our micro-framework to solve this!

 - `index.php`
    - I am not even kidding!
    - serializes/unserializes the table
    - allows interactions (given a player token, or a new player joining)
    - simple `switch` case + URI matching + HTTP method matching
    
 - that's it, that's our framework!
    - it works just fine!
    - but is tied to the web SAPI
       - what is the web SAPI? (audience?)
       - what's the problem?
          - react/http support
          - integration with firewall components
          - etc.
          
# moving to ZF2
          
 - let's make our code more forward-compliant
    - let's introduce zf2
    - with zf2 comes the entire module system
    - we split "actions" into functions
    - we get request/response object support
    - we add helpers as services
    - we have error handling
    - we can import modules that solve ACL for us
    - we can import modules that solve CORS for us
    - we can have templating
    - etcetera
    
# moving forward (ZF3?)
          
 - introducing PSR-7 middlewares
    - what is PSR-7? (ask the audience)
    - let's make our app forward-compatible!
    - zend-stratigility
 
 - introducing zend-expressive
    - not the zend framework 3 that you were expecting, huh?
    - let's code an example!
       - "index" route (show status)
       - "join" route (provides cookie, redirects to "index")
       - "get-cards" route (shows cards, only with cookie)
       - "pass" route (action, only with cookie, redirects to "index")
       - "bet" route (action, only with cookie, redirects to "index")
       
 - let's replace session!
    - `Ocramius/StorageLessSession`
    
 - congrats!
    - Now your app is compatible with any PSR-7 (stratigility-compatible) middleware
    - can work with symfony 3
    - can work with slim
    - can work with zend-mvc v3
    - can work WITHOUT the PHP web SAPI!
    - can work without `ext/session`
 
 - can deploy standalone
 - can deploy as part of a framework
 - can move it wherever we want
    - we achieved what we wanted: minimal framework coupling!