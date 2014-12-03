from server
    {data:....}
    
to server
    {data:....}
    
////////////

{data:
    action  :   init_game,
    color   :   red|blue|green,
    castle  :   {[1-15]:red|blue|green, [1-15]:red|blue|green, [1-15]:red|blue|green},
    points  :   ,
    }
    
EXAMPLE

{data:
    action  :   init_game,
    color   :   red,
    castle  :   {1:red, 3:blue, 15:green},
    points  :   1000,
    }
    
////////////        