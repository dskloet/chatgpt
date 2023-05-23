# Shell script to use ChatGPT API

1. Get your API key here: https://platform.openai.com/account/api-keys
2. Create a file called `api-key` with the following content, replacing the key with your own key:
```
OPENAI_API_KEY="sk-loremipsumqwoirevkdfjbteuu38rffebwbf2jf34ghjdfvd"
```
3. Run `g3` (for GPT-3.5-turbo) or `g4` (for GPT-4) without any parameters.
4. Enter your prompt.
5. Press Ctrl+D

Example:
````
$ ./g3
What's your question?
How can I replace all instances of "red" with "blue" in a text file on Linux?
^D
Sending request...
Answer:

You can use the `sed` command to replace all instances of "red" with "blue" in
a text file on Linux. The command would be:

```
sed -i 's/red/blue/g' filename.txt
```

This will replace all occurrences of "red" with "blue" in the file
`filename.txt`. The `-i` option tells `sed` to edit the file in place, and the
`s/red/blue/g` command tells `sed` to substitute "blue" for "red" globally
(i.e., for all occurrences in the file).

GPT-3.5 cost: 0.042 cents
````
