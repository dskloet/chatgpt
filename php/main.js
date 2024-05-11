import * as v from './vanilla.js';

const click = v.event('click');
const inputEvent = v.event('input');
const value = v.property('value');
const inputValue = v.eventProperty('input', 'value');

const { type } = v.attributes;

const { div, button } = v.tags;
const textInput = v.tag('input', (ob) => [inputValue(ob), type('text')]);
const checkbox = v.tag('input', (ob) => [inputChecked(ob), type('checkbox')]);

const systemPrompt = "You are a helpful assistant but your answers are short like you are a robot. You don't omit useful information but you don't repeat the question and you don't use filler words or pleasantries and you don't try to be polite.";
const model = 'gpt-3.5-turbo-0125'
const maxTokens = 1000;

const apiKey = window.location.hash.substring(1);

async function askGpt(question) {
  if (!apiKey) {
    return "apiKey is missing";
  }
  const requestData = {
    model,
    messages: [
      { role: 'system', content: systemPrompt },
      { role: 'user', content: question }
    ],
    temperature: 0,
    max_tokens: maxTokens,
  };

  const apiUrl = "./api.php";

  const response = await fetch(apiUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${apiKey}`
    },
    body: JSON.stringify(requestData)
  });
  const data = await response.json();

  if (data.error?.message) {
    return data.error.message;
  }
  const answer = data.choices.map(choice => choice.message.content).join('\n');
  return answer;
}

window.askGpt = askGpt;

const q = new v.Observable('');
const answer = new v.Observable('');

async function send() {
  answer.value = await askGpt(q.value);
}

v.body(
  div(
    'Question: ',
    textInput(q),
    button(
      'Send',
      click(send)
    )
  ),
  div('Answer: ', answer)
);
