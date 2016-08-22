
var i;

myObject = {
    day: 'Thursday',
    cat: 'Tekke'
};

for (i in myObject) {
    console.log(i, myObject[i]);
}

for(var i in myObject) {
    console.log(i, myObject[i]);
}


var anotherVarDeclaration = 'error';