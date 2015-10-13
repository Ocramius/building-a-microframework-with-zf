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
    - react/http
    - phly/http
    - phly/conduit
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

# let's code our micr-oframework to solve this!

 - `index.php`
    - I am not even kidding!
